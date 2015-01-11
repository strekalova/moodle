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
 * Unit tests for export/import description for category in the Moodle XML format.
 *
 * @package    qformat_xml
 * @copyright  2014 Nikita Nikitsky, Volgograd State Technical University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/format/xml/format.php');
require_once($CFG->dirroot . '/question/format.php');
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');

/**
 * Unit tests for the matching question definition class.
 *
 * @copyright  2014 Nikita Nikitsky, Volgograd State Technical University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qformat_xml_description_test extends advanced_testcase {

    /**
     * Create object qformat_xml for test.
     * @param string $filename with name for testing file.
     * @return new qformat_xml.
     */
    public function create_qformat($filename) {
        global $DB;

        $qformat = new qformat_xml();
        $contexts = $DB->get_records('context');
        $COURSE = $DB->get_record('course', array('id' => 1), '*', MUST_EXIST);
        $importfile = __DIR__ . '/fixtures/' .$filename;
        $realfilename = $filename;

        $qformat->setContexts($contexts);
        $qformat->setCourse($COURSE);
        $qformat->setFilename($importfile);
        $qformat->setRealfilename($realfilename);
        $qformat->setMatchgrades('error');
        $qformat->setCatfromfile(1);
        $qformat->setContextfromfile(1);
        $qformat->setStoponerror(1);
        $qformat->setCattofile(1);
        $qformat->setContexttofile(1);

        return $qformat;
    }

    /**
     * Check xml for compliance.
     * @param string $expectedxml with correct string.
     * @param string $xml you want to check.
     */
    public function assert_same_xml($expectedxml, $xml) {
        $this->assertEquals(str_replace("\r\n", "", $expectedxml),
                str_replace("\n", "",  str_replace("\r\n", "\n", $xml)));
    }

    /**
     * Check imported category
     * @param string $name imported category.
     * @param string $info imported category.
     * @param int $infoformat imported category.
     * @param string $parentname imported category.
     */
    public function assert_category_imported($name, $info, $infoformat, $parentname) {
        global $DB;
        $category = $DB->get_record('question_categories', array('name' => $name), '*', MUST_EXIST);
        if ($parentname != '') {
            $parent = $DB->get_record('question_categories', array('name' => $parentname), '*', MUST_EXIST)->id;
        } else {
            $parent = 0;
        }

        $this->assertEquals($category->info, $info);
        $this->assertEquals($category->infoformat, $infoformat);
        $this->assertEquals($category->parent, $parent);
    }

    public function test_import_category() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $qformat = $this->create_qformat('category_description_test1.xml');

        $imported = $qformat->importprocess(null);
        $this->assertTrue($imported, 'Not imported category_description_test1.xml');

        $this->assert_category_imported('Alpha', 'This is Alpha category for test', FORMAT_MOODLE, '');
    }

    public function test_import_category_first_nesting() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $qformat = $this->create_qformat('category_description_test2.xml');

        $imported = $qformat->importprocess(null);
        $this->assertTrue($imported, 'Not imported category_description_test2.xml');

        $this->assert_category_imported('Beta', 'This is Beta category for test', FORMAT_HTML, '');

        $this->assert_category_imported('Gamma', 'This is Gamma category for test', FORMAT_PLAIN, 'Beta');
    }

    public function test_import_category_second_nesting() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $qformat = $this->create_qformat('category_description_test3.xml');

        $imported = $qformat->importprocess(null);
        $this->assertTrue($imported, 'Not imported category_description_test3.xml');

        $this->assert_category_imported('Delta', 'This is Delta category for test', FORMAT_WIKI, '');

        $this->assert_category_imported('Epsilon', 'This is Epsilon category for test', FORMAT_MARKDOWN, 'Delta');

        $this->assert_category_imported('Zeta', 'This is Zeta category for test', FORMAT_MOODLE, 'Epsilon');
    }

    public function test_import_category_one_child() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $qformat = $this->create_qformat('category_description_test4.xml');

        $imported = $qformat->importprocess(null);
        $this->assertTrue($imported, 'Not imported category_description_test4.xml');

        $this->assert_category_imported('Eta', 'This is Eta category for test', FORMAT_HTML, '');

        $this->assert_category_imported('Theta', 'This is Theta category for test', FORMAT_PLAIN, 'Eta');
    }

    public function test_import_category_one_child_one_child() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $qformat = $this->create_qformat('category_description_test5.xml');

        $imported = $qformat->importprocess(null);
        $this->assertTrue($imported, 'Not imported category_description_test5.xml');

        $this->assert_category_imported('Iota', 'This is Iota category for test', FORMAT_WIKI, '');

        $this->assert_category_imported('Kappa', 'This is Kappa category for test', FORMAT_MARKDOWN, 'Iota');

        $this->assert_category_imported('Lambda', 'This is Lambda category for test', FORMAT_MOODLE, 'Kappa');
    }

    public function test_import_category_two_children() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $qformat = $this->create_qformat('category_description_test6.xml');

        $imported = $qformat->importprocess(null);
        $this->assertTrue($imported, 'Not imported category_description_test6.xml');

        $this->assert_category_imported('Mu', 'This is Mu category for test', FORMAT_HTML, '');

        $this->assert_category_imported('Nu', 'This is Nu category for test', FORMAT_PLAIN, 'Mu');

        $this->assert_category_imported('Xi', 'This is Xi category for test', FORMAT_WIKI, 'Mu');
    }

    public function test_import_category_old_format() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $qformat = $this->create_qformat('category_description_test7.xml');

        $imported = $qformat->importprocess(null);
        $this->assertTrue($imported, 'Not imported category_description_test7.xml');

        $this->assert_category_imported('Omicron', '', FORMAT_MOODLE, '');
    }

    public function test_import_category_one_child_old_format() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $qformat = $this->create_qformat('category_description_test8.xml');

        $imported = $qformat->importprocess(null);
        $this->assertTrue($imported, 'Not imported category_description_test8.xml');

        $this->assert_category_imported('Pi', '', FORMAT_MOODLE, '');

        $this->assert_category_imported('Rho', '', FORMAT_MOODLE, 'Pi');
    }

    public function test_import_categories_record_reverse_order() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $qformat = $this->create_qformat('category_description_test9.xml');

        $imported = $qformat->importprocess(null);
        $this->assertTrue($imported, 'Not imported category_description_test9.xml');

        $this->assert_category_imported('Sigma', 'This is Sigma category for test', FORMAT_PLAIN, '');

        $this->assert_category_imported('Tau', 'This is Tau category for test', FORMAT_HTML, 'Sigma');
    }

    public function test_export_category() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $qformat = $this->create_qformat('category_description_test1.xml');

        $imported = $qformat->importprocess(null);
        $this->assertTrue($imported, 'Not imported category_description_test1.xml');

        $category = $DB->get_record('question_categories', array('name' => 'Alpha'), '*', MUST_EXIST);
        $qformat->setCategory($category);

        $xml = preg_replace('/(<!-- question: )([0-9]+)(  -->)/', '', $qformat->exportprocess());
        $file = preg_replace('/(<!-- question: )([0-9]+)(  -->)/', '',
                                file_get_contents(__DIR__ . '/fixtures/category_description_test1.xml'));
        $this->assert_same_xml($file, $xml);
    }

    public function test_export_category_first_nesting() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $qformat = $this->create_qformat('category_description_test2.xml');

        $imported = $qformat->importprocess(null);
        $this->assertTrue($imported, 'Not imported category_description_test2.xml');

        $category = $DB->get_record('question_categories', array('name' => 'Gamma'), '*', MUST_EXIST);
        $qformat->setCategory($category);

        $xml = preg_replace('/(<!-- question: )([0-9]+)(  -->)/', '', $qformat->exportprocess());
        $file = preg_replace('/(<!-- question: )([0-9]+)(  -->)/', '',
                                file_get_contents(__DIR__ . '/fixtures/category_description_test2.xml'));
        $this->assert_same_xml($file, $xml);
    }

    public function test_export_category_second_nesting() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $qformat = $this->create_qformat('category_description_test3.xml');

        $imported = $qformat->importprocess(null);
        $this->assertTrue($imported, 'Not imported category_description_test3.xml');

        $category = $DB->get_record('question_categories', array('name' => 'Zeta'), '*', MUST_EXIST);
        $qformat->setCategory($category);

        $xml = preg_replace('/(<!-- question: )([0-9]+)(  -->)/', '', $qformat->exportprocess());
        $file = preg_replace('/(<!-- question: )([0-9]+)(  -->)/', '',
                                file_get_contents(__DIR__ . '/fixtures/category_description_test3.xml'));
        $this->assert_same_xml($file, $xml);
    }

    public function test_export_category_one_child() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $qformat = $this->create_qformat('category_description_test4.xml');

        $imported = $qformat->importprocess(null);
        $this->assertTrue($imported, 'Not imported category_description_test4.xml');

        $category = $DB->get_record('question_categories', array('name' => 'Eta'), '*', MUST_EXIST);
        $qformat->setCategory($category);

        $xml = preg_replace('/(<!-- question: )([0-9]+)(  -->)/', '', $qformat->exportprocess());
        $file = preg_replace('/(<!-- question: )([0-9]+)(  -->)/', '',
                                file_get_contents(__DIR__ . '/fixtures/category_description_test4.xml'));
        $this->assert_same_xml($file, $xml);
    }

    public function test_export_category_one_child_one_child() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $qformat = $this->create_qformat('category_description_test5.xml');

        $imported = $qformat->importprocess(null);
        $this->assertTrue($imported, 'Not imported category_description_test5.xml');

        $category = $DB->get_record('question_categories', array('name' => 'Iota'), '*', MUST_EXIST);
        $qformat->setCategory($category);

        $xml = preg_replace('/(<!-- question: )([0-9]+)(  -->)/', '', $qformat->exportprocess());
        $file = preg_replace('/(<!-- question: )([0-9]+)(  -->)/', '',
                                file_get_contents(__DIR__ . '/fixtures/category_description_test5.xml'));
        $this->assert_same_xml($file, $xml);
    }

    public function test_export_category_two_children() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $qformat = $this->create_qformat('category_description_test6.xml');

        $imported = $qformat->importprocess(null);
        $this->assertTrue($imported, 'Not imported category_description_test6.xml');

        $category = $DB->get_record('question_categories', array('name' => 'Mu'), '*', MUST_EXIST);
        $qformat->setCategory($category);

        $xml = preg_replace('/(<!-- question: )([0-9]+)(  -->)/', '', $qformat->exportprocess());
        $file = preg_replace('/(<!-- question: )([0-9]+)(  -->)/', '',
                                file_get_contents(__DIR__ . '/fixtures/category_description_test6.xml'));
        $this->assert_same_xml($file, $xml);
    }
}
