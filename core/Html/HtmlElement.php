<?php

namespace Core\Html;

use Core\IdGenerator;
use Core\File;

abstract class HtmlElement
{
    const off = 0;

    protected ?string $style = null;
    protected ?string $id = null;
    protected ?string $class = null;
    protected string $resp = '';
    protected string $dataId;
    protected static int $counter = 0;
    protected $cssContent = null;

    /**
     * Cada elemento debe definir su tag
     */
    abstract protected function getTagName(): string;

    /**
     * Factory fluido moderno
     */
    public static function in(...$children): static
    {
        return new static(...$children);
    }

    /**
     * Constructor moderno compatible con herencia
     */
    public function __construct(...$children)
    {
        $this->generateDataId();
        $this->resp = $this->buildChildren($children);
    }

    /**
     * Procesa los children de forma robusta
     */
    protected function buildChildren(array $children): string
    {
        $output = [];

        foreach ($children as $child) {

            if (is_object($child) && method_exists($child, 'build')) {
                $output[] = $child->build();
            } elseif (is_string($child) || is_numeric($child)) {
                $output[] = (string) $child;
            } elseif (is_array($child)) {
                $output[] = $this->buildChildren($child);
            }
        }

        return implode('', $output);
    }

    /**
     * Genera data-id único
     */
    protected function generateDataId(): void
    {
        $tagName = $this->getTagName();
        $this->dataId = IdGenerator::generate($tagName);
    }

    /**
     * Permite establecer un data-id personalizado
     */
    public function setDataId(string $dataId): static
    {
        if (IdGenerator::isIdUsed($dataId)) {

            $counter = 1;
            $newDataId = $dataId . '-' . $counter;

            while (IdGenerator::isIdUsed($newDataId)) {
                $counter++;
                $newDataId = $dataId . '-' . $counter;
            }

            $dataId = $newDataId;
        }

        IdGenerator::registerUsedId($dataId);
        $this->dataId = $dataId;

        return $this;
    }

    public function getDataId(): string
    {
        return $this->dataId;
    }

    protected function getDataIdAttribute(): string
    {
        return ' data-id="' . $this->dataId . '"';
    }

    public function setId(string $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function class(string|array $class): static
    {
        if (is_array($class)) {
            $this->class = implode(' ', $class);
        } else {
            $this->class = $class;
        }

        return $this;
    }

    public function setStyle(array|object $styles): static
    {
        if (is_array($styles) || is_object($styles)) {
            foreach ($styles as $k => $v) {
                $this->style .= $k . ':' . $v . ';';
            }
        }

        return $this;
    }

    protected function getId(): string
    {
        return empty($this->id) ? '' : ' id="' . $this->id . '"';
    }

    protected function getClass(): string
    {
        return empty($this->class) ? '' : ' class="' . $this->class . '"';
    }

    protected function getStyle(): string
    {
        return empty($this->style) ? '' : ' style="' . $this->style . '"';
    }

    protected function getAttributes(): string
    {
        return $this->getDataIdAttribute()
            . $this->getId()
            . $this->getClass()
            . $this->getStyle();
    }

    /**
     * CSS automático por clase
     */
    protected function getClassPath(): string
    {
        $reflection = new \ReflectionClass(static::class);
        return $reflection->getFileName();
    }

    protected function getClassDirectory(): string
    {
        return dirname($this->getClassPath());
    }

    protected function getClassName(): string
    {
        $reflection = new \ReflectionClass(static::class);
        return $reflection->getShortName();
    }

    protected function buildCssPath(): string
    {
        return $this->getClassDirectory()
            . DIRECTORY_SEPARATOR
            . $this->getClassName()
            . '.css';
    }

    protected function loadCss(): void
    {
        if ($this->cssContent !== null) {
            return;
        }

        $cssPath = $this->buildCssPath();
        $this->cssContent = File::loadFile($cssPath, 'css');
    }

    protected function getCssTag(): string
    {
        if ($this->cssContent === null) {
            $this->loadCss();
        }

        if ($this->cssContent !== false && !empty($this->cssContent)) {
            return '<style>' . $this->cssContent . '</style>';
        }

        return '';
    }

    public function render(): void
    {
        echo $this->build();
    }

    /**
     * Render final
     */
    public function build(): string
    {
        $tag = $this->getTagName();
        $cssTag = $this->getCssTag();

        return $cssTag
            . '<' . $tag . $this->getAttributes() . '>'
            . $this->resp
            . '</' . $tag . '>';
    }
}
