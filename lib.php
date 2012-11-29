<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * XCache cache store main library.
 *
 * @package    cachestore_xcache
 * @category   cache
 * @copyright  2012 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class cachestore_xcache extends cache_store implements cache_is_configurable {

    /**
     * The name of this store instance.
     * @var string
     */
    protected $name;

    /**
     * The definition used when this instance was initialised.
     * @var cache_definition
     */
    protected $definition = null;

    /**
     * The prefix to use for keys. Allows you to utilise many store instances for a single backend.
     * @var string
     */
    protected $prefix = '';

    /**
     * Maximum time to live in minutes.
     * @var int
     */
    protected $maxttl = 60;

    /**
     * The time to live for entries in the cache.
     * This gets set when the store is initialised with a definition.
     * @var int
     */
    protected $ttl = 0;

    /**
     * Static method to check that the XCache stores requirements have been met.
     *
     * It checks that the XCache extension has been loaded and that its size is more than 0.
     * If its size is 0 then its considered to be disabled.
     *
     * @return bool True if the stores software/hardware requirements have been met and it can be used. False otherwise.
     */
    public static function are_requirements_met() {
        return extension_loaded('xcache') && (int)ini_get('xcache.var_size') > 0;
    }

    /**
     * Static method to check if a store is usable with the given mode.
     *
     * @param int $mode One of cache_store::MODE_*
     * @return bool True if the mode is supported.
     */
    public static function is_supported_mode($mode) {
        return ($mode === self::MODE_APPLICATION);
    }

    /**
     * Returns the supported features as a binary flag.
     *
     * @param array $configuration The configuration of a store to consider specifically.
     * @return int The supported features.
     */
    public static function get_supported_features(array $configuration = array()) {
        $supports = self::SUPPORTS_NATIVE_TTL;
        if (array_key_exists('maxtll', $configuration) && $configuration['maxttl'] === 0) {
            $supports += self::SUPPORTS_DATA_GUARANTEE;
        }
        return $supports;
    }

    /**
     * Returns true if the store instance guarantees data.
     *
     * @return bool
     */
    public function supports_data_guarantee() {
        return $this->maxttl === 0;
    }

    /**
     * Returns the supported modes as a binary flag.
     *
     * @param array $configuration The configuration of a store to consider specifically.
     * @return int The supported modes.
     */
    public static function get_supported_modes(array $configuration = array()) {
        return self::MODE_APPLICATION;
    }

    /**
     * Used to control the ability to add an instance of this store through the admin interfaces.
     *
     * @return bool True if the user can add an instance, false otherwise.
     */
    public static function can_add_instance() {
        return true;
    }

    /**
     * Constructs an instance of the cache store.
     *
     * This method should not create connections or perform and processing, it should be used
     *
     * @param string $name The name of the cache store
     * @param array $configuration The configuration for this store instance.
     */
    public function __construct($name, array $configuration = array()) {
        $this->name = $name;
        if (array_key_exists('prefix', $configuration) && $configuration['prefix'] !== '') {
            $this->prefix = $configuration['prefix'] . '_';
        }
        if (array_key_exists('maxttl', $configuration) && $configuration['maxttl'] !== '') {
            $this->maxttl = (int)$configuration['maxttl'];
        }
    }

    /**
     * Returns the name of this store instance.
     * @return string
     */
    public function my_name() {
        return $this->name;
    }

    /**
     * Initialises a new instance of the cache store given the definition the instance is to be used for.
     *
     * This function should prepare any given connections etc.
     *
     * @param cache_definition $definition
     * @return bool
     */
    public function initialise(cache_definition $definition) {
        $this->definition = $definition;
        if ($this->maxttl !== 0) {
            // Don't forget to convert maxttl from minutes to seconds.
            $this->ttl = min($definition->get_ttl(), $this->maxttl * 60);
        }
        return true;
    }

    /**
     * Returns true if this cache store instance has been initialised.
     * @return bool
     */
    public function is_initialised() {
        return ($this->definition !== null);
    }

    /**
     * Returns true if this cache store instance is ready to use.
     * @return bool
     */
    public function is_ready() {
        // No set up is actually required, providing apc is installed and enabled.
        return true;
    }

    /**
     * Retrieves an item from the cache store given its key.
     *
     * @param string $key The key to retrieve
     * @return mixed The data that was associated with the key, or false if the key did not exist.
     */
    public function get($key) {
        // xcache_get returns null if the item doesn't exist.
        $result = xcache_get($this->prefix.$key);
        if ($result === null) {
            return false;
        }
        return $result;
    }

    /**
     * Retrieves several items from the cache store in a single transaction.
     *
     * If not all of the items are available in the cache then the data value for those that are missing will be set to false.
     *
     * @param array $keys The array of keys to retrieve
     * @return array An array of items from the cache. There will be an item for each key, those that were not in the store will
     *      be set to false.
     */
    public function get_many($keys) {
        $outcomes = array();
        foreach ($keys as $key) {
            $outcomes[$key] = $this->get($key);
        }
        return $outcomes;
    }

    /**
     * Sets an item in the cache given its key and data value.
     *
     * @param string $key The key to use.
     * @param mixed $data The data to set.
     * @return bool True if the operation was a success false otherwise.
     */
    public function set($key, $data) {
        return xcache_set($this->prefix.$key, $data, $this->definition->get_ttl());
    }

    /**
     * Sets many items in the cache in a single transaction.
     *
     * @param array $keyvaluearray An array of key value pairs. Each item in the array will be an associative array with two
     *      keys, 'key' and 'value'.
     * @return int The number of items successfully set. It is up to the developer to check this matches the number of items
     *      sent ... if they care that is.
     */
    public function set_many(array $keyvaluearray) {
        $count = 0;
        foreach ($keyvaluearray as $pair) {
            if ($this->set($pair['key'], $pair['value'])) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Deletes an item from the cache store.
     *
     * @param string $key The key to delete.
     * @return bool Returns true if the operation was a success, false otherwise.
     */
    public function delete($key) {
        return xcache_unset($this->prefix.$key);
    }

    /**
     * Deletes several keys from the cache in a single action.
     *
     * @param array $keys The keys to delete
     * @return int The number of items successfully deleted.
     */
    public function delete_many(array $keys) {
        $count = 0;
        foreach ($keys as $key) {
            if ($this->delete($key)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Purges the cache deleting all items within it.
     *
     * @return boolean True on success. False otherwise.
     */
    public function purge() {
        if ($this->is_initialised() && $this->prefix !== '') {
            xcache_unset_by_prefix($this->prefix);
        } else {
            xcache_unset_by_prefix('');
        }
        return true;
    }

    /**
     * Performs any necessary clean up when the store instance is being deleted.
     */
    public function cleanup() {
        $this->purge();
    }

    /**
     * Generates an instance of the cache store that can be used for testing.
     *
     * Returns an instance of the cache store, or false if one cannot be created.
     *
     * @param cache_definition $definition
     * @return cache_store
     */
    public static function initialise_test_instance(cache_definition $definition) {
        $name = 'XCache test';
        $cache = new cachestore_xcache($name, array('prefix' => 'test'));
        $cache->initialise($definition);
        return $cache;
    }

    /**
     * Given the data from the add instance form this function creates a configuration array.
     *
     * @param stdClass $data
     * @return array
     */
    public static function config_get_configuration_array($data) {
        $config = array(
            'preset' => '',
            'maxttl' => 10
        );
        if (isset($data->preset)) {
            $config['preset'] = $data->preset;
        }
        if (isset($data->maxttl)) {
            $config['maxttl'] = abs((int)$data->maxttl);
        }
        return $config;
    }

    /**
     * Allows the cache store to set its data against the edit form before it is shown to the user.
     *
     * @param moodleform $editform
     * @param array $config
     */
    public static function config_set_edit_form_data(moodleform $editform, array $config) {
        $data = array(
            'preset' => '',
            'maxttl' => 10
        );
        if (!empty($config['preset'])) {
            $data['preset'] = $config['preset'];
        }
        if (!empty($config['maxttl'])) {
            $data['maxttl'] = $config['maxttl'];
        }
        $editform->set_data($data);
    }
}