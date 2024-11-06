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

use DateTime;
use PHPUnit\Framework\TestCase;
use Qubus\Form\FormBuilder\Control;
use Qubus\Form\FormBuilder\Input;

use function json_encode;

/**
 * Tests for <input> in Bootstrap Form generator
 */
class InputTest extends TestCase
{
    /** @var Input $input */
    protected Input $input;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp(): void
    {
        $this->input = new Input();
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
        $input = new Input(
            ['type' => 'text', 'name' => 'test', 'foo' => 'bar'],
            ['id' => 'testRender', 'class' => 'subinput', 'placeholder' => 'A Test']
        );

        $attrs = [
            'id'          => 'testRender',
            'class'       => 'subinput',
            'placeholder' => 'A Test',
            'type'        => 'text',
            'min'         => null,
            'max'         => null,
            'maxlength'   => null,
            'pattern'     => null,
            'name'        => 'test',
            'value'       => null,
            'required'    => null,
        ];
        $this->assertSame($attrs, $input->getAttr());
        $this->assertSame('bar', $input->getOption('foo'));
        $this->assertSame('Test', $input->getDescription());
    }

    /**
     * Test setting and getting an attribute
     */
    public function testGetAttr()
    {
        $ret = $this->input->setAttr('foo', 'bar');
        $this->assertSame($this->input, $ret);
        $this->assertSame('bar', $this->input->getAttr('foo'));
    }

    /**
     * Test setting and getting an attribute as date
     */
    public function testGetAttrDateTime()
    {
        $date = new DateTime('now');
        $this->input->setAttr('min', $date);
        $this->assertSame((string) $date->format('c'), $this->input->getAttr('min'));
        $this->assertSame($date->format('c'), $this->input->getAttr('min'));
    }

    /**
     * Test setting and getting an attribute an array and value object
     */
    public function testGetAttrJson()
    {
        $this->input->setAttr('foo', [10, 52]);
        $this->assertSame('[10,52]', $this->input->getAttr('foo'));
        $this->assertSame(json_encode([10, 52]), $this->input->getAttr('foo'));

        $val = json_encode(['alpha' => 'lima', 'beta' => 'mike']);
        $this->input->setAttr('charlie', $val);
        $this->assertSame($val, $this->input->getAttr('charlie'));
    }

    /**
     * Test setting and getting an attribute as control.
     */
    public function testGetAttrControl()
    {
        $date = new DateTime('now');

        $control = $this->getMockBuilder(Control::class)->setMethods(['getValue'])->getMockForAbstractClass();
        $control->expects($this->exactly(3))
            ->method('getValue')
            ->willReturn($this->onConsecutiveCalls('42', '51', $date));

        $this->input->setAttr(['min' => $control]);
        $this->assertSame('42', $this->input->getAttr('min'));
        $this->assertSame('51', $this->input->getAttr('min'));
        $this->assertSame($date->format('c'), $this->input->getAttr('min'));
    }

    /**
     * Test setting and getting an attribute as date
     */
    public function testGetAttrsDateTime()
    {
        $date = new DateTime('now');
        $this->input->setAttr(['min' => $date->format('c')]);
        $this->assertSame(
            [
                'type'        => 'text',
                'placeholder' => null,
                'min'         => $date->format('c'),
                'max'         => null,
                'maxlength'   => null,
                'pattern'     => null,
                'name'        => '',
                'value'       => null,
                'required'    => null,
                'id'          => $this->input->getId(),
                'class'       => null,
            ],
            $this->input->getAttr()
        );
    }

    /**
     * Test setting and getting an attribute an array and value object
     */
    public function testGetAttrsJson()
    {
        $this->input->setAttr('foo', [10, 52]);

        $val = json_encode(['alpha' => 'lima', 'beta' => 'mike']);
        $this->input->setAttr('charlie', $val);

        $this->assertSame(
            [
                'type'        => 'text',
                'placeholder' => null,
                'min'         => null,
                'max'         => null,
                'maxlength'   => null,
                'pattern'     => null,
                'name'        => '',
                'value'       => null,
                'required'    => null,
                'id'          => $this->input->getId(),
                'class'       => null,
                'foo'         => '[10,52]',
                'charlie'     => $val,
            ],
            $this->input->getAttr()
        );
    }

    /**
     * Test setting and getting an option
     */
    public function testGetOption()
    {
        $ret = $this->input->setOption('foo', 'bar');
        $this->assertSame($this->input, $ret);
        $this->assertSame('bar', $this->input->getOption('foo'));
    }

    /**
     * Test setting and getting the name
     */
    public function testGetName()
    {
        $ret = $this->input->setOption('name', 'foo');
        $this->assertSame($this->input, $ret);
        $this->assertSame('foo', $this->input->getName());
        $this->assertSame('foo', $this->input->getAttr('name'));
    }

    /**
     * Test setting and getting the value
     */
    public function testGetValue()
    {
        $ret = $this->input->setValue('bar99');
        $this->assertSame($this->input->getValue(), $ret);
        $this->assertSame('bar99', $this->input->getValue());
        $this->assertSame('bar99', $this->input->getAttr('value'));

        $val = json_encode(['foo' => 'bar']);
        $this->input->setValue($val);
        $this->assertSame($val, $this->input->getValue());
        $this->assertSame($val, $this->input->getAttr('value'));
    }

    /**
     * Test setting and getting the description
     */
    public function testGetDescription()
    {
        $ret = $this->input->setOption(['name' => 'Foo Bar']);
        $this->assertSame($this->input, $ret);
        $this->assertSame('Foo Bar', $this->input->getDescription());
    }

    /**
     * Test rendering a text input with control group
     */
    public function testRenderControlGroup()
    {
        echo "\n";

        $html = <<<HTML
<div >
<label for="inputEmail">Email</label>
<input type="text" name="email" id="inputEmail">
</div>
HTML;

        $this->input->setOption([
            'label' => true,
            'name'  => 'email',
            'id'    => 'inputEmail',
        ]);

        $this->input->setAttr([
            'id'   => 'inputEmail',
            'name' => 'email',
        ]);

        $rendered = $this->input;
        var_export((string) $this->input);
        $this->assertSame($html, $rendered);
    }
}
