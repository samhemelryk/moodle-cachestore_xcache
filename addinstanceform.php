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
 * The add instance form for the XCache store.
 *
 * @package    cachestore_xcache
 * @copyright  2012 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/cache/forms.php');
require_once($CFG->dirroot.'/cache/stores/xcache/lib.php');

/**
 * Form for adding an XCache instance.
 *
 * @copyright  2012 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cachestore_xcache_addinstance_form extends cachestore_addinstance_form {

    /**
     * Adds the desired form elements.
     */
    protected function configuration_definition() {
        $form = $this->_form;

        $form->addElement('text', 'prefix', get_string('prefix', 'cachestore_xcache'), array('maxsize' => 8));
        $form->setType('prefix', PARAM_ALPHANUM);
        $form->addHelpButton('prefix', 'prefix', 'cachestore_xcache');

        $form->addElement('text', 'maxttl', get_string('maxttl', 'cachestore_xcache'));
        $form->setType('maxttl', PARAM_INT);
        $form->addHelpButton('maxttl', 'maxttl', 'cachestore_xcache');
        $form->setDefault('maxttl', 10);
    }

    /**
     * Performs custom validation for us.
     *
     * @param array $data An array of data sent to the form.
     * @param array $files An array of files sent to the form.
     * @return array An array of errors.
     */
    protected function configuration_validation($data, $files) {
        $errors = array();
        if (!array_key_exists('prefix', $data)) {
            $prefix = '';
        } else {
            $prefix = clean_param($data['prefix'], PARAM_ALPHANUM);
        }

        $factory = cache_factory::instance();
        $config = $factory->create_config_instance();
        foreach ($config->get_all_stores() as $store) {
            if ($store['plugin'] !== 'xcache') {
                continue;
            }
            if (empty($store['configuration']['prefix'])) {
                $storeprefix = '';
            } else {
                $storeprefix = $store['configuration']['prefix'];
            }
            if ($storeprefix === $prefix) {
                $errors['prefix'] = get_string('erroruniqueprefix');
            }
        }
        return $errors;
    }
}