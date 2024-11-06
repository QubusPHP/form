<?php

/**
 * Qubus\Form
 *
 * @link       https://github.com/QubusPHP/form
 * @copyright  2023 Joshua Parker <joshua@joshuaparker.dev>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Tests\Form;

use PHPUnit\Framework\TestCase;
use Qubus\Form\FormBuilder\Div;
use Qubus\Form\FormBuilder\Element;

/**
 * Tests for <div> in Bootstrap Form generator
 */
class DivTest extends TestCase
{
    /** @var Div */
    protected Div $div;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->div = new Div();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    /**
     * Test the form constructor
     */
    public function testConstruct()
    {
        $div = new Div(['foo' => 'bar'], ['red' => 'brown']);

        $this->assertSame('brown', $div->getAttr('red'));
        $this->assertSame('bar', $div->getOption('foo'));
    }

    /**
     * Test getting an attribute
     */
    public function testSetAttr()
    {
        $this->div->setAttr('foo', 'bar');
        $this->assertSame('bar', $this->div->getAttr('foo'));
    }

    /**
     * Test getting an option
     */
    public function testSetOption()
    {
        $this->div->setOption('foo', 'bar');
        $this->assertSame('bar', $this->div->getOption('foo'));
    }

    /**
     * Test adding a child div
     */
    public function testAdd()
    {
        $child = new Div();
        $div = $this->div->add($child);

        $this->assertSame($this->div, $div);
        $this->assertContains($child, $this->div->getChildren());
        $this->assertSame($this->div, $child->getParent());
    }

    /**
     * Test method end
     *
     * @depends testAdd
     */
    public function testEnd()
    {
        $child = new Div();
        $this->div->add($child);

        $this->assertSame($this->div, $child->end());
    }
}
