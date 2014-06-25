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
 * Unit tests for coderunner nodejs questions.
 * @group qtype_coderunner
 * Assumed to be run after python questions have been tested, so focuses
 * only on nodejs-specific aspects.
 *
 * @package    qtype
 * @subpackage coderunner
 * @copyright  2014 Richard Lobb, University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/coderunner/tests/coderunnertestcase.php');

/**
 * Unit tests for coderunner nodejs questions.
 */
class qtype_coderunner_nodejs_question_test extends qtype_coderunner_testcase {

    public function test_good_sqr_function() {
        $this->check_language_available('nodejs');
        $q = $this->make_question('sqrnodejs');
        $response = array('answer' => "function sqr(n) {\n  return n * n;\n}\n");
        list($mark, $grade, $cache) = $q->grade_response($response);
        $this->assertEquals(1, $mark);
        $this->assertEquals(question_state::$gradedright, $grade);
        $this->assertTrue(isset($cache['_testoutcome']));
        $testOutcome = unserialize($cache['_testoutcome']);
        $this->assertEquals(4, count($testOutcome->testResults));
        $this->assertTrue($testOutcome->allCorrect());
    }


    public function test_bad_sqr_function() {
        // Omit the declaration; should give a runtime error as strict mode enforced.
        $this->check_language_available('nodejs');
        $q = $this->make_question('sqrnodejs');
        $response = array('answer' => "function sqr(n) {\n  result = n * n;    return result\n}\n");
        list($mark, $grade, $cache) = $q->grade_response($response);
        $this->assertEquals(0, $mark);
        $this->assertEquals(question_state::$gradedwrong, $grade);
        $this->assertTrue(isset($cache['_testoutcome']));
        $testOutcome = unserialize($cache['_testoutcome']);
        $this->assertFalse($testOutcome->allCorrect());
    }
}
