<?php

declare(strict_types=1);

namespace benda95280\Fishing\Form;

use Closure;
use function is_array;

class CustomForm extends BaseForm
{
    public const DROPDOWN = "dropdown";
    public const INPUT = "input";
    public const LABEL = "label";
    public const SLIDER = "slider";
    public const STEP_SLIDER = "step_slider";
    public const TOGGLE = "toggle";

    public function __construct(Closure $onSubmit, ?Closure $onClose = null)
    {
        parent::__construct($onSubmit, $onClose);
        $this->setType(self::CUSTOM_FORM_TYPE);
        $this->data["content"] = [];
    }

    public final function addDropdown(string $text, array $options, ?int $defaultIndexOption = null, ?string $label = null): self
    {
        return $this->addContent(
            self::DROPDOWN,
            $text,
            ["options" => $options, "default" => $defaultIndexOption],
            $label
        );
    }

    public final function addContent(string $type, string $text, array $contentData, ?string $label): self
    {
        $this->data["content"][] = array_merge(
            [
                "type" => $type,
                "text" => $text
            ],
            array_filter(
                $contentData,
                static function ($value): bool {//Remove null fields
                    return $value !== null;
                }
            )
        );

        return $this->addDataLabel($label);
    }

    public final function addInput(string $text, string $placeHolder = "", ?string $defaultText = null, ?string $label = null): self
    {
        return $this->addContent(
            self::INPUT,
            $text,
            ["placeholder" => $placeHolder, "default" => $defaultText],
            $label
        );
    }

    public final function addLabel(string $text, ?string $label = null): self
    {
        return $this->addContent(self::LABEL, $text, [], $label);
    }

    public final function addSlider(string $text, int $min, int $max, ?int $step = null, ?int $defaultStep = null, ?string $label = null): self
    {
        return $this->addContent(
            self::SLIDER,
            $text,
            [
                "min" => $min,
                "max" => $max,
                "step" => $step,
                "default" => $defaultStep
            ],
            $label
        );
    }

    public final function addStepSlider(string $text, array $steps, ?int $defaultStep = null, ?string $label = null): self
    {
        return $this->addContent(
            self::STEP_SLIDER,
            $text,
            ["steps" => $steps, "default" => $defaultStep],
            $label
        );
    }

    public final function addToggle(string $text, ?bool $default = null, ?string $label = null): self
    {
        return $this->addContent(
            self::TOGGLE,
            $text,
            ["default" => $default],
            $label
        );
    }

    protected function processLabels(mixed &$data): void
    {
        if (is_array($data)) {
            $tmpDataLbl = [];
            foreach ($data as $k => $v) {
                $tmpDataLbl[$this->dataLabels[$k]] = $v;
            }

            $data = $tmpDataLbl;
        }
    }

}