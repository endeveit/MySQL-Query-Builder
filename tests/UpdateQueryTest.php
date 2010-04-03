<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
require_once "..".DIRECTORY_SEPARATOR."autoload.php";
require_once 'PHPUnit/Framework.php';
 
class UpdateQueryTest extends PHPUnit_Framework_TestCase
{
    public function testEmptyUpdate()
    {
        try {
            $q = new UpdateQuery(array('test'));
            $sql = $q->sql();
            $this->fail("allowed to generate empty query");
        } catch (LogicException $e) {
        }
    }

    public function testFullUpdate()
    {
        $q = new UpdateQuery(array('test'));
        $q->setValues(array(
            'qwe' => 'qweqwe'
        ));

        $this->assertEquals('UPDATE `test` AS `t0` SET `t0`.`qwe` = :p1', $q->sql());

        $params = $q->parameters();
        $this->assertEquals('qweqwe', $params[':p1']);

        // shortcut
        $q = new UpdateQuery('test');
        $q->qwe = 'qweqwe';

        $this->assertEquals('UPDATE `test` AS `t0` SET `t0`.`qwe` = :p1', $q->sql());

        $params = $q->parameters();
        $this->assertEquals('qweqwe', $params[':p1']);
    }

    public function testConditionalUpdate()
    {
        $q = new UpdateQuery(array('test'));
        $q->setValues(array(
            'qwe' => 'qweqwe'
        ));
        $q->setWhere(new Condition('=', new Field('a'), 'b'));

        $this->assertEquals('UPDATE `test` AS `t0` SET `t0`.`qwe` = :p1 WHERE `t0`.`a` = :p2', $q->sql());

        $params = $q->parameters();
        $this->assertEquals('qweqwe', $params[':p1']);
        $this->assertEquals('b', $params[':p2']);
    }

    public function testMultitableUpdate()
    {
        $q = new UpdateQuery(array('test', 'test2'));
        $q->setValues(array(
            array(new Field('field1'), 'value1'),
            array(new Field('field2', 1), 'value2')
        ));

        $this->assertEquals('UPDATE `test` AS `t0`, `test2` AS `t1` SET `t0`.`field1` = :p1, `t1`.`field2` = :p2', $q->sql());
 
        $params = $q->parameters();
        $this->assertEquals('value1', $params[':p1']);
        $this->assertEquals('value2', $params[':p2']);
    }

    public function testConditionalMultitableUpdate()
    {
        $q = new UpdateQuery(array('test', 'test2'));
        $q->setValues(array(
            array(new Field('field1'), 'value1'),
            array(new Field('field2', 1), 'value2')
        ));
        $q->setWhere(new Condition('<', new Field('date', 1), '2004-10-11'));

        $this->assertEquals('UPDATE `test` AS `t0`, `test2` AS `t1` SET `t0`.`field1` = :p1, `t1`.`field2` = :p2 WHERE `t1`.`date` < :p3', $q->sql());
 
        $params = $q->parameters();
        $this->assertEquals('value1', $params[':p1']);
        $this->assertEquals('value2', $params[':p2']);
        $this->assertEquals('2004-10-11', $params[':p3']);
    }

    public function testLimit()
    {
        $q = new UpdateQuery('test');
        $q->setValues(array(
            'qwe' => 'qweqwe'
        ));
        $q->setLimit(10);

        $this->assertEquals('UPDATE `test` AS `t0` SET `t0`.`qwe` = :p1 LIMIT 10', $q->sql());

        $params = $q->parameters();
        $this->assertEquals('qweqwe', $params[':p1']);
    }

    public function testOrderBy()
    {
        $q = new UpdateQuery(array('test'));
        $q->setValues(array(
            'qwe' => 'qweqwe'
        ));
        $q->setLimit(10);
        $q->setOrderBy(array(new Field('date')));

        $this->assertEquals('UPDATE `test` AS `t0` SET `t0`.`qwe` = :p1 ORDER BY `t0`.`date` ASC LIMIT 10', $q->sql());

        $params = $q->parameters();
        $this->assertEquals('qweqwe', $params[':p1']);
    }

    public function testOrderLimitOnMultiple()
    {
        try {
            $q = new UpdateQuery(array('test', 'test2', 'test3'));
            $q->setLimit(10);
            $this->fail('LIMIT should not be allowed on multi-table queries');
        } catch (LogicException $e) {
        }

        try {
            $q = new UpdateQuery(array('test', 'test2', 'test3'));
            $q->setOrderBy(array(new Field('field1')));
            $this->fail('ORDER BY should not be allowed on multi-table queries');
        } catch (LogicException $e) {
        }
    }

    public function testResettingOfSETClause()
    {
        $q = new UpdateQuery('test');
        $q->setValues(array('foo' => 'bar'));

        $sql = $q->sql();
        $this->assertEquals('UPDATE `test` AS `t0` SET `t0`.`foo` = :p1', $sql);

        $q->setValues(array());
        $q->baz = 'bar';

        $sql = $q->sql();
        $this->assertEquals('UPDATE `test` AS `t0` SET `t0`.`baz` = :p1', $sql);
    }

    public function testAlternateSetSyntax()
    {
        $q = new UpdateQuery('test');
        $q->setValues(array(array('foo', 'bar'), array('baz', 'bar')));

        $sql = $q->sql();

        $this->assertEquals('UPDATE `test` AS `t0` SET `t0`.`foo` = :p1, `t0`.`baz` = :p2', $sql);
    }

    public function testExpressionParameters()
    {
        $q = new UpdateQuery('test');
        $q->setValues(array(
            'foo' => new ExpressionParameter(new Field('foo'), '+', 1),
        ));

        $sql = $q->sql();

        $this->assertEquals('UPDATE `test` AS `t0` SET `t0`.`foo` = `t0`.`foo` + :p1', $sql);
    }
}