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
use Qubus\Form\Form;
use Qubus\Form\FormBuilder;
use Qubus\Form\FormBuilder\Element;

/**
 * Tests <form> for Bootstrap Form generator
 */
class FormTest extends TestCase
{
    /** @var Form */
    protected Form $form;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp(): void
    {
        $this->form = new Form();
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
        $form = new Form(['foo' => 'bar'], ['red' => 'brown']);

        $this->assertSame('brown', $form->getAttr('red'));
        $this->assertSame('post', $form->getAttr('method'));
        $this->assertSame('bar', $form->getOption('foo'));
    }

    /**
     * Set setting single attributes
     */
    public function testSetAttr()
    {
        $this->form->setAttr('foo', 'bar');
        $this->form->setAttr('zoo', '123');
        $attr = $this->form->getAttr();

        $this->assertArrayHasKey('method', $attr);
        $this->assertSame('post', $attr['method']);

        $this->assertArrayHasKey('foo', $attr);
        $this->assertSame('bar', $attr['foo']);

        $this->assertArrayHasKey('zoo', $attr);
        $this->assertSame('123', $attr['zoo']);
    }

    /**
     * Test setting multiple attributes
     */
    public function testSetAttrArray()
    {
        $this->form->setAttr([
            'foo' => 'bar',
            'zoo' => '123',
        ]);
        $attr = $this->form->getAttr();

        $this->assertArrayHasKey('method', $attr);
        $this->assertSame('post', $attr['method']);

        $this->assertArrayHasKey('foo', $attr);
        $this->assertSame('bar', $attr['foo']);

        $this->assertArrayHasKey('zoo', $attr);
        $this->assertSame('123', $attr['zoo']);
    }

    /**
     * Test getting an attribute
     */
    public function testGetAttr()
    {
        $this->form->setAttr('foo', 'bar');
        $this->assertSame('bar', $this->form->getAttr('foo'));
    }

    /**
     * Test setting single options
     */
    public function testSetOption()
    {
        $this->form->setOption('foo', 'bar');
        $this->form->setOption('zoo', false);
        $options = $this->form->getOptions(false);

        $this->assertArrayHasKey('foo', $options);
        $this->assertSame('bar', $options['foo']);

        $this->assertArrayHasKey('zoo', $options);
        $this->assertFalse($options['zoo']);

        $this->form->setOption('foo', null);
        $options = $this->form->getOptions();

        $this->assertArrayNotHasKey('foo', $options);
        $this->assertArrayHasKey('zoo', $options);
    }

    /**
     * Test setting multiple options
     */
    public function testSetOptionArray()
    {
        $this->form->setOption(['foo' => 'bar', 'zoo' => false]);
        $options = $this->form->getOptions();

        $this->assertArrayHasKey('foo', $options);
        $this->assertSame('bar', $options['foo']);

        $this->assertArrayHasKey('zoo', $options);
        $this->assertSame(false, $options['zoo']);

        $this->form->setOption(['foo' => null]);
        $options = $this->form->getOptions();

        $this->assertArrayNotHasKey('foo', $options);
        $this->assertArrayHasKey('zoo', $options);
    }

    /**
     * Test getting an option
     */
    public function testGetOption()
    {
        $this->form->setOption('foo', 'bar');
        $this->assertSame('bar', $this->form->getOption('foo'));

        $this->assertNull($this->form->getOption('non-existent'));
    }

    /**
     * Test getting single options with bubble
     *
     * @depends testGetOption
     */
    public function testGetOptionBubble()
    {
        $this->form->setOption('zoo', 999);
        $this->form->setOption('foo', 'bar');
        $this->form->setOption('error:min', 'minimal');

        $element = $this->getMockBuilder(Element::class)->getMockForAbstractClass();
        $element->setOption('zoo', 123);
        $this->form->add($element);

        $this->assertSame(123, $element->getOption('zoo'));
        $this->assertSame('bar', $element->getOption('foo'));
        $this->assertSame('minimal', $element->getOption('error:min'));
        $this->assertSame(FormBuilder::$options['error:max'], $element->getOption('error:max'));

        $this->assertNull($element->getOption('non-existent'));
    }

    /**
     * Test getting the id
     */
    public function testGetId()
    {
        $this->form->setOption(['id' => 'foobar']);
        $this->assertSame('foobar', $this->form->getId());

        $this->form->setOption(['id' => 'foobar']);
        $id = $this->form->getId();
        $this->form->setAttr(['name' => 'test']);
        $this->assertSame($id, $this->form->getId());

        $this->form->setOption(['id' => 'foobar']);
        $this->form->setAttr(['name' => 'test']);
        $id = $this->form->getId();
        $this->assertSame('foobar', $this->form->getId());
    }

    /**
     * Test adding HTML to a form.
     */
    public function testAdd()
    {
        $div = '<div>Test</div>';
        $form = $this->form->add($div);

        $this->assertSame($this->form, $form);
        $this->assertContains($div, $this->form->getChildren());
    }

    /**
     * Test adding an element to a form
     */
    public function testAddElement()
    {
        $element = $this->getMockBuilder(Element::class)->getMockForAbstractClass();
        $form = $this->form->add($element);

        $this->assertSame($this->form, $form);
        $this->assertContains($element, $this->form->getChildren());
        $this->assertSame($this->form, $element->getParent());
    }

    /**
     * Test rendering a form
     */
    public function testRender()
    {
        $html = <<<HTML
<form method="post" id="form">

</form>
HTML;
        $this->form->setOption(['id' => 'form']);
        $this->assertSame($html, (string) $this->form);
    }

    /**
     * Test rendering a form with attributes.
     *
     * @depends testRender
     */
    public function testRenderWithAttr()
    {
        $html = <<<HTML
<form method="GET" id="form" class="form-horizontal" action="test.php" data-foo="data-foo">

</form>
HTML;

        $this->form->setAttr([
            'method'   => 'GET',
            'id'       => 'form',
            'action'   => 'test.php',
            'class'    => 'form-horizontal',
            'data-foo' => 'data-foo',
            'data-bar' => false,
        ]);

        $this->assertSame($html, (string) $this->form);
    }
}
