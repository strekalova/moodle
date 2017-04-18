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
class qformat_xml_category_info_testcase extends advanced_testcase {
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
        $this->assertEquals(preg_replace('/( +)/', "", str_replace("\n", "",
            str_replace("\r\n", "\n", str_replace("\t", "\n", $expectedxml)))),
            preg_replace('/( +)/', "", str_replace("\n", "",  str_replace( "\r\n", "\n", str_replace( "\t", "\n", $xml)))));
    }

    /**
     * Check xml for compliance.
     * @param string $expectedxml with correct string.
     * @param string $xml you want to check.
     */
    public function assert_same_xml_random_category($expectedxml, $xml) {
        $str1 = preg_replace('/( +)/', "",
                str_replace("\n", "", str_replace("\r\n", "\n", str_replace("\t", "\n", $expectedxml))));

        $str2 = preg_replace('/( +)/', "", str_replace("\n", "",
                str_replace( "\r\n", "\n", str_replace( "\t", "\n", $xml))));

        $str1 = str_replace("unknownhost+" + '/[0-9]+/' + "+", "", $str1);
        $this->assertEquals($str1, $str2);
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

    /**
     * Test for import category
     * Alpha
     */
    public function test_import_category() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $qformat = $this->create_qformat('category_description_import_category.xml');
        $imported = $qformat->importprocess(null);
        $this->assertTrue($imported, 'Not imported category_description_import_category.xml');
        $this->assert_category_imported('Alpha', 'This is Alpha category for test', FORMAT_MOODLE, '');
    }

    /**
     * Test for import category first nesting
     * Beta
     *    \_Gamma
     */
    public function test_import_category_first_nesting() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $qformat = $this->create_qformat('category_description_first_nesting.xml');
        $imported = $qformat->importprocess(null);
        $this->assertTrue($imported, 'Not imported category_description_first_nesting.xml');
        $this->assert_category_imported('Beta', 'This is Beta category for test', FORMAT_HTML, '');
        $this->assert_category_imported('Gamma', 'This is Gamma category for test', FORMAT_PLAIN, 'Beta');
    }

    /**
     * Test for import category second nesting
     * Delta
     *     \_Epsilon
     *              \_Zeta
     */
    public function test_import_category_second_nesting() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $qformat = $this->create_qformat('category_description_second_nesting.xml');
        $imported = $qformat->importprocess(null);
        $this->assertTrue($imported, 'Not imported category_description_second_nesting.xml');
        $this->assert_category_imported('Delta', 'This is Delta category for test', FORMAT_WIKI, '');
        $this->assert_category_imported('Epsilon', 'This is Epsilon category for test', FORMAT_MARKDOWN, 'Delta');
        $this->assert_category_imported('Zeta', 'This is Zeta category for test', FORMAT_MOODLE, 'Epsilon');
    }

    /**
     * Test for import category one child
     * Eta
     *    \_Theta
     */
    public function test_import_category_one_child() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $qformat = $this->create_qformat('category_description_one_child.xml');
        $imported = $qformat->importprocess(null);
        $this->assertTrue($imported, 'Not imported category_description_one_child.xml');
        $this->assert_category_imported('Eta', 'This is Eta category for test', FORMAT_HTML, '');
        $this->assert_category_imported('Theta', 'This is Theta category for test', FORMAT_PLAIN, 'Eta');
    }

    /**
     * Test for import category one child one child
     * Iota
     *     \_Kappa
     *            \_Lambda
     */
    public function test_import_category_one_child_one_child() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $qformat = $this->create_qformat('category_description_one_child_one_child.xml');
        $imported = $qformat->importprocess(null);
        $this->assertTrue($imported, 'Not imported category_description_one_child_one_child.xml');
        $this->assert_category_imported('Iota', 'This is Iota category for test', FORMAT_WIKI, '');
        $this->assert_category_imported('Kappa', 'This is Kappa category for test', FORMAT_MARKDOWN, 'Iota');
        $this->assert_category_imported('Lambda', 'This is Lambda category for test', FORMAT_MOODLE, 'Kappa');
    }

    /**
     * Test for import category two children
     * Mu
     *  \\_Nu
     *   \_Xi
     */
    public function test_import_category_two_children() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $qformat = $this->create_qformat('category_description_two_children.xml');
        $imported = $qformat->importprocess(null);
        $this->assertTrue($imported, 'Not imported category_description_two_children.xml');
        $this->assert_category_imported('Mu', 'This is Mu category for test', FORMAT_HTML, '');
        $this->assert_category_imported('Nu', 'This is Nu category for test', FORMAT_PLAIN, 'Mu');
        $this->assert_category_imported('Xi', 'This is Xi category for test', FORMAT_WIKI, 'Mu');
    }
    /**
     * Test for import category old format (without format)
     * Omicron
     */
    public function test_import_category_old_format() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $qformat = $this->create_qformat('category_description_old_format.xml');
        $imported = $qformat->importprocess(null);
        $this->assertTrue($imported, 'Not imported category_description_old_format.xml');
        $this->assert_category_imported('Omicron', '', FORMAT_MOODLE, '');
    }
    /**
     * Test for import category one child old format (without format)
     * Pi
     *   \_Rho
     */
    public function test_import_category_one_child_old_format() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $qformat = $this->create_qformat('category_description_one_child_old_format.xml');
        $imported = $qformat->importprocess(null);
        $this->assertTrue($imported, 'Not imported category_description_one_child_old_format.xml');
        $this->assert_category_imported('Pi', '', FORMAT_MOODLE, '');
        $this->assert_category_imported('Rho', '', FORMAT_MOODLE, 'Pi');
    }

    /**
     * Test for import category record reverse (in xml child go first)
     * Sigma
     *      \_Tau
     */
    public function test_import_categories_record_reverse_order() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $qformat = $this->create_qformat('category_description_record_reverse_order.xml');
        $imported = $qformat->importprocess(null);
        $this->assertTrue($imported, 'Not imported category_description_record_reverse_order.xml');
        $this->assert_category_imported('Sigma', 'This is Sigma category for test', FORMAT_PLAIN, '');
        $this->assert_category_imported('Tau', 'This is Tau category for test', FORMAT_HTML, 'Sigma');
    }

    /**
     * Test for export category
     * Alpha
     */
    public function test_export_category() {
        global $DB;
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $qformat = $this->create_qformat('category_description_export_category.xml');

        $category = $generator->create_question_category(array( 'id' => '284000',
                                                                'name' => 'Alpha',
                                                                'contextid' => '2',
                                                                'info' => 'This is Alpha category for test',
                                                                'infoformat' => '0',
                                                                'stamp' => make_unique_id_code(),
                                                                'parent' => '0',
                                                                'sortorder' => '999'));

        $question = $generator->create_question('truefalse', null, array(
                                                                'category' => $category->id,
                                                                'name' => 'AlphaQuestion',
                                                                'questiontext' => array(
                                                                                'format' => '1',
                                                                                'text' => '<p>TestingAlphaQuestion</p>'),
                                                                'generalfeedback' => array('format' => '1', 'text' => ''),
                                                                'correctanswer' => '1',
                                                                'feedbacktrue' => array('format' => '1', 'text' => ''),
                                                                'feedbackfalse' => array('format' => '1', 'text' => ''),
                                                                'penalty' => '1'));

        $qformat->setCategory($category);

        $xml = preg_replace('/(<!-- question: )([0-9]+)(  -->)/', '', $qformat->exportprocess());

        $file = preg_replace('/(<!-- question: )([0-9]+)(  -->)/', '',
            file_get_contents(__DIR__ . '/fixtures/category_description_export_category.xml'));
        $this->assert_same_xml($file, $xml);
    }

    /**
     * Test for export category first nesting
     * Beta
     *    \_Gamma
     */
    public function test_export_category_first_nesting() {
        global $DB;
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $qformat = $this->create_qformat('category_description_first_nesting.xml');
        $categorybeta = $generator->create_question_category(array( 'id' => '314000',
                                                                    'name' => 'Beta',
                                                                    'contextid' => '2',
                                                                    'info' => 'This is Beta category for test',
                                                                    'infoformat' => '1',
                                                                    'stamp' => make_unique_id_code(),
                                                                    'parent' => '0',
                                                                    'sortorder' => '999'));

        $categorygamma = $generator->create_question_category(array('id' => '314001',
                                                                    'name' => 'Gamma',
                                                                    'contextid' => '2',
                                                                    'info' => 'This is Gamma category for test',
                                                                    'infoformat' => '2',
                                                                    'stamp' => make_unique_id_code(),
                                                                    'parent' => '314000',
                                                                    'sortorder' => '999'));

        $question  = $generator->create_question('truefalse', null, array(
                                                                    'category' => $categorygamma->id,
                                                                    'name' => 'Gamma Question',
                                                                    'questiontext' => array(
                                                                                    'format' => '1',
                                                                                    'text' => '<p>Testing Gamma Question</p>'),
                                                                    'generalfeedback' => array('format' => '1', 'text' => ''),
                                                                    'correctanswer' => '1',
                                                                    'feedbacktrue' => array('format' => '1', 'text' => ''),
                                                                    'feedbackfalse' => array('format' => '1', 'text' => ''),
                                                                    'penalty' => '1'));
        $qformat->setCategory($categorybeta);
        $qformat->setCategory($categorygamma);
        $xml = preg_replace('/(<!-- question: )([0-9]+)(  -->)/', '', $qformat->exportprocess());
        $file = preg_replace('/(<!-- question: )([0-9]+)(  -->)/', '',
            file_get_contents(__DIR__ . '/fixtures/category_description_first_nesting.xml'));
        $this->assert_same_xml($file, $xml);
    }

    /**
     * Test for export category second nesting
     * Delta
     *     \_Epsilon
     *              \_Zeta
     */
    public function test_export_category_second_nesting() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $qformat = $this->create_qformat('category_description_second_nesting.xml');

        $categorydelta = $generator->create_question_category(array('id' => '314000',
                                                                    'name' => 'Delta',
                                                                    'contextid' => '2',
                                                                    'info' => 'This is Delta category for test',
                                                                    'infoformat' => '3',
                                                                    'stamp' => make_unique_id_code(),
                                                                    'parent' => '0',
                                                                    'sortorder' => '999'));

        $categoryepsilon = $generator->create_question_category(array( 'id' => '314001',
                                                                        'name' => 'Epsilon',
                                                                        'contextid' => '2',
                                                                        'info' => 'This is Epsilon category for test',
                                                                        'infoformat' => '4',
                                                                        'stamp' => make_unique_id_code(),
                                                                        'parent' => '314000',
                                                                        'sortorder' => '999'));

        $categoryzeta = $generator->create_question_category(array( 'id' => '314002',
                                                                    'name' => 'Zeta',
                                                                    'contextid' => '2',
                                                                    'info' => 'This is Zeta category for test',
                                                                    'infoformat' => '0',
                                                                    'stamp' => make_unique_id_code(),
                                                                    'parent' => '314001',
                                                                    'sortorder' => '999'));

        $question  = $generator->create_question('truefalse', null, array(
                                                                    'category' => $categoryzeta->id,
                                                                    'name' => 'Zeta Question',
                                                                    'questiontext' => array(
                                                                                    'format' => '1',
                                                                                    'text' => '<p>Testing Zeta Question</p>'),
                                                                    'generalfeedback' => array('format' => '1', 'text' => ''),
                                                                    'correctanswer' => '1',
                                                                    'feedbacktrue' => array('format' => '1', 'text' => ''),
                                                                    'feedbackfalse' => array('format' => '1', 'text' => ''),
                                                                    'penalty' => '1'));
        $qformat->setCategory($categorydelta);
        $qformat->setCategory($categoryepsilon);
        $qformat->setCategory($categoryzeta);
        $xml = preg_replace('/(<!-- question: )([0-9]+)(  -->)/', '', $qformat->exportprocess());
        $file = preg_replace('/(<!-- question: )([0-9]+)(  -->)/', '',
                                file_get_contents(__DIR__ . '/fixtures/category_description_second_nesting.xml'));
        $this->assert_same_xml($file, $xml);
    }

    /**
     * Test for export category one child
     * Eta
     *    \_Theta
     */
    public function test_export_category_one_child() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $qformat = $this->create_qformat('category_description_one_child.xml');
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $categoryeta = $generator->create_question_category(array( 'id' => '314000',
                                                                    'name' => 'Eta',
                                                                    'contextid' => '2',
                                                                    'info' => 'This is Eta category for test',
                                                                    'infoformat' => '1',
                                                                    'stamp' => make_unique_id_code(),
                                                                    'parent' => '0',
                                                                    'sortorder' => '999'));

        $etaquestion = $generator->create_question('truefalse', null, array(
                                                                    'category' => $categoryeta->id,
                                                                    'name' => 'Eta Question',
                                                                    'questiontext' => array(
                                                                                    'format' => '1',
                                                                                    'text' => '<p>Testing Eta Question</p>'),
                                                                    'generalfeedback' => array('format' => '1', 'text' => ''),
                                                                    'correctanswer' => '1',
                                                                    'feedbacktrue' => array('format' => '1', 'text' => ''),
                                                                    'feedbackfalse' => array('format' => '1', 'text' => ''),
                                                                    'penalty' => '1'));

        $categorytheta = $generator->create_question_category(array('id' => '314001',
                                                                    'name' => 'Theta',
                                                                    'contextid' => '2',
                                                                    'info' => 'This is Theta category for test',
                                                                    'infoformat' => '2',
                                                                    'stamp' => make_unique_id_code(),
                                                                    'parent' => '314000',
                                                                    'sortorder' => '999'));

        $thetaquestion  = $generator->create_question('truefalse', null, array(
                                                                    'category' => $categorytheta->id,
                                                                    'name' => 'Theta Question',
                                                                    'questiontext' => array(
                                                                                    'format' => '1',
                                                                                    'text' => '<p>Testing Theta Question</p>'),
                                                                    'generalfeedback' => array('format' => '1', 'text' => ''),
                                                                    'correctanswer' => '1',
                                                                    'feedbacktrue' => array('format' => '1', 'text' => ''),
                                                                    'feedbackfalse' => array('format' => '1', 'text' => ''),
                                                                    'penalty' => '1'));
        $qformat->setCategory($categoryeta);
        $xml = preg_replace('/(<!-- question: )([0-9]+)(  -->)/', '', $qformat->exportprocess());
        $file = preg_replace('/(<!-- question: )([0-9]+)(  -->)/', '',
                                file_get_contents(__DIR__ . '/fixtures/category_description_one_child.xml'));
        $this->assert_same_xml($file, $xml);
    }

    /**
     * Test for export category one child one child
     * Iota
     *     \_Kappa
     *            \_Lambda
     */
    public function test_export_category_one_child_one_child() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $qformat = $this->create_qformat('category_description_one_child_one_child.xml');
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $categoryiota = $generator->create_question_category(array('id' => '314000',
                                                                'name' => 'Iota',
                                                                'contextid' => '2',
                                                                'info' => 'This is Iota category for test',
                                                                'infoformat' => '3',
                                                                'stamp' => make_unique_id_code(),
                                                                'parent' => '0',
                                                                'sortorder' => '999'));

        $iotaquestion  = $generator->create_question('truefalse', null, array(
                                                                    'category' => $categoryiota->id,
                                                                    'name' => 'Iota Question',
                                                                    'questiontext' => array(
                                                                                'format' => '1',
                                                                                'text' => '<p>Testing Iota Question</p>'),
                                                                    'generalfeedback' => array('format' => '1', 'text' => ''),
                                                                    'correctanswer' => '1',
                                                                    'feedbacktrue' => array('format' => '1', 'text' => ''),
                                                                    'feedbackfalse' => array('format' => '1', 'text' => ''),
                                                                    'penalty' => '1'));

        $categorykappa = $generator->create_question_category(array('id' => '314001',
                                                                    'name' => 'Kappa',
                                                                    'contextid' => '2',
                                                                    'info' => 'This is Kappa category for test',
                                                                    'infoformat' => '4',
                                                                    'stamp' => make_unique_id_code(),
                                                                    'parent' => '314000',
                                                                    'sortorder' => '999'));

        $kappaquestion  = $generator->create_question('truefalse', null, array(
                                                                    'category' => $categorykappa->id,
                                                                    'name' => 'Kappa Question',
                                                                    'questiontext' => array(
                                                                                'format' => '1',
                                                                                'text' => '<p>Testing Kappa Question</p>'),
                                                                    'generalfeedback' => array('format' => '1', 'text' => ''),
                                                                    'correctanswer' => '1',
                                                                    'feedbacktrue' => array('format' => '1', 'text' => ''),
                                                                    'feedbackfalse' => array('format' => '1', 'text' => ''),
                                                                    'penalty' => '1'));

        $categorylambda = $generator->create_question_category(array('id' => '314002',
                                                                'name' => 'Lambda',
                                                                'contextid' => '2',
                                                                'info' => 'This is Lambda category for test',
                                                                'infoformat' => '0',
                                                                'stamp' => make_unique_id_code(),
                                                                'parent' => '314001',
                                                                'sortorder' => '999'));

        $lambdaquestion  = $generator->create_question('truefalse', null, array(
                                                                    'category' => $categorylambda->id,
                                                                    'name' => 'Lambda Question',
                                                                    'questiontext' => array(
                                                                                    'format' => '1',
                                                                                    'text' => '<p>Testing Lambda Question</p>'),
                                                                    'generalfeedback' => array('format' => '1', 'text' => ''),
                                                                    'correctanswer' => '1',
                                                                    'feedbacktrue' => array('format' => '1', 'text' => ''),
                                                                    'feedbackfalse' => array('format' => '1', 'text' => ''),
                                                                    'penalty' => '1'));

        $qformat->setCategory($categoryiota);
        $xml = preg_replace('/(<!-- question: )([0-9]+)(  -->)/', '', $qformat->exportprocess());
        $file = preg_replace('/(<!-- question: )([0-9]+)(  -->)/', '',
                                file_get_contents(__DIR__ . '/fixtures/category_description_one_child_one_child.xml'));
        $this->assert_same_xml($file, $xml);
    }

    /**
     * Test for export category two children
     * Mu
     *  \\_Nu
     *   \_Xi
     */
    public function test_export_category_two_children() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $qformat = $this->create_qformat('category_description_two_children.xml');
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $categorymu = $generator->create_question_category(array('id' => '314000',
                                                                'name' => 'Mu',
                                                                'contextid' => '2',
                                                                'info' => 'This is Mu category for test',
                                                                'infoformat' => '1',
                                                                'stamp' => make_unique_id_code(),
                                                                'parent' => '0',
                                                                'sortorder' => '999')
                                                            );

        $muquestion  = $generator->create_question('truefalse', null, array(
                                                                    'category' => $categorymu->id,
                                                                    'name' => 'Mu Question',
                                                                    'questiontext' => array(
                                                                                    'format' => '1',
                                                                                    'text' => '<p>testing Mu Question</p>'),
                                                                    'generalfeedback' => array('format' => '1', 'text' => ''),
                                                                    'correctanswer' => '1',
                                                                    'feedbacktrue' => array('format' => '1', 'text' => ''),
                                                                    'feedbackfalse' => array('format' => '1', 'text' => ''),
                                                                    'penalty' => '1'));

        $categorynu = $generator->create_question_category(array('id' => '314001',
                                                                'name' => 'Nu',
                                                                'contextid' => '2',
                                                                'info' => 'This is Nu category for test',
                                                                'infoformat' => '2',
                                                                'stamp' => make_unique_id_code(),
                                                                'parent' => '314000',
                                                                'sortorder' => '999'));

        $nuquestion  = $generator->create_question('truefalse', null, array(
                                                                    'category' => $categorynu->id,
                                                                    'name' => 'Nu Question',
                                                                    'questiontext' => array(
                                                                                    'format' => '1',
                                                                                    'text' => '<p>Testing Nu Question</p>'),
                                                                    'generalfeedback' => array('format' => '1', 'text' => ''),
                                                                    'correctanswer' => '1',
                                                                    'feedbacktrue' => array('format' => '1', 'text' => ''),
                                                                    'feedbackfalse' => array('format' => '1', 'text' => ''),
                                                                    'penalty' => '1'));

        $categoryxi = $generator->create_question_category(array('id' => '314001',
                                                                'name' => 'Xi',
                                                                'contextid' => '2',
                                                                'info' => 'This is Xi category for test',
                                                                'infoformat' => '3',
                                                                'stamp' => make_unique_id_code(),
                                                                'parent' => '314000',
                                                                'sortorder' => '999'));

        $xiquestion  = $generator->create_question('truefalse', null, array(
                                                                    'category' => $categoryxi->id,
                                                                    'name' => 'Xi Question',
                                                                    'questiontext' => array(
                                                                                    'format' => '1',
                                                                                    'text' => '<p>Testing Xi Question</p>'),
                                                                    'generalfeedback' => array('format' => '1', 'text' => ''),
                                                                    'correctanswer' => '1',
                                                                    'feedbacktrue' => array('format' => '1', 'text' => ''),
                                                                    'feedbackfalse' => array('format' => '1', 'text' => ''),
                                                                    'penalty' => '1'));

        $qformat->setCategory($categorymu);
        $xml = preg_replace('/(<!-- question: )([0-9]+)(  -->)/', '', $qformat->exportprocess());
        $file = preg_replace('/(<!-- question: )([0-9]+)(  -->)/', '',
                                file_get_contents(__DIR__ . '/fixtures/category_description_two_children.xml'));
        $this->assert_same_xml($file, $xml);
    }
}