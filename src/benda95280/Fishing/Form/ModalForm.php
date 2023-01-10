<?php

declare(strict_types=1);

namespace benda95280\Fishing\Form;

use Closure;
use InvalidArgumentException;

class ModalForm extends BaseForm
{

    public function __construct(Closure $onSubmit, ?Closure $onClose = null)
    {
        parent::__construct($onSubmit, $onClose);
        $this->setType(self::MODAL_FORM_TYPE);
        $this->setFirstButton("")
            ->setSecondButton("")
            ->setMessage("");
    }

    public final function setMessage(string $message): self
    {
        $this->data["content"] = $message;

        return $this;
    }

    public final function setSecondButton(string $text): self
    {
        return $this->setButton(2, $text);
    }

    /**
     * @param int $button It must be 1 or 2
     * @param string $text The button text
     *
     * @return ModalForm
     */
    public final function setButton(int $button, string $text): self
    {
        if ($button < 1 || $button > 2) {
            throw new InvalidArgumentException("The button value must be 1 or 2.");
        }
        $this->data["button{$button}"] = $text;

        return $this;
    }

    public final function setFirstButton(string $text): self
    {
        return $this->setButton(1, $text);
    }

    public final function getMessage(): string
    {
        return $this->data["content"];
    }

    /**
     * Returns the first button text.
     * @return string
     */
    public final function getFirstButton(): string
    {
        return $this->getButton(1);
    }

    public final function getButton(int $button): string
    {
        if ($button < 1 || $button > 2) {
            throw new InvalidArgumentException("The button value must be 1 or 2.");
        }

        return $this->data["button{$button}"];
    }

    /**
     * Returns the second button text.
     * @return string
     */
    public final function getSecondButton(): string
    {
        return $this->getButton(2);
    }

    protected function processLabels(mixed &$data): void
    {
    }
}