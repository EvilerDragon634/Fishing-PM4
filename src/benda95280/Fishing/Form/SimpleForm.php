<?php

declare(strict_types=1);

namespace benda95280\Fishing\Form;

use Closure;
use function is_int;

class SimpleForm extends BaseForm
{

    public const IMAGE_TYPE_PATH = "path";
    public const IMAGE_TYPE_URL = "url";

    public function __construct(Closure $onSubmit, ?Closure $onClose = null)
    {
        parent::__construct($onSubmit, $onClose);
        $this->setType(self::FORM_TYPE);
        $this->setMessage("");
        $this->data["buttons"] = [];
    }

    /**
     * Sets the message before all the buttons
     *
     * @param string $message
     * @return Form
     */
    public final function setMessage(string $message): self
    {
        $this->data["content"] = $message;

        return $this;
    }

    public final function getMessage(): string
    {
        return $this->data["content"];
    }

    /**
     * Adds a button without images.
     *
     * @param string $text
     * @param string|null $label
     * @return Form
     */
    public final function addClassicButton(string $text, ?string $label = null): self
    {
        return $this->addButton($text, null, "", $label);
    }

    public final function addButton(string $text, ?string $imageType = null, string $imagePath = "", ?string $label = null): self
    {
        $data["text"] = $text;
        if ($imageType !== null) {
            $data["image"]["type"] = $imageType;
            $data["image"]["data"] = $imagePath;
        }

        $this->data["buttons"][] = $data;

        return $this->addDataLabel($label);
    }

    /**
     * Adds a button with an image taken from the web.
     *
     * @param string $text
     * @param string $url
     * @param string|null $label
     * @return Form
     */
    public final function addWebImageButton(string $text, string $url, ?string $label = null): self
    {
        return $this->addButton($text, self::IMAGE_TYPE_URL, $url, $label);
    }

    /**
     * Adds a button with a local Minecraft image.
     *
     * @param string $text
     * @param string $imagePath
     * @param string|null $label
     * @return Form
     */
    public final function addLocalImageButton(string $text, string $imagePath, ?string $label = null): self
    {
        return $this->addButton($text, self::IMAGE_TYPE_PATH, $imagePath, $label);
    }

    protected function processLabels(mixed &$data): void
    {
        if (is_int($data) && isset($this->dataLabels[$data])) {
            $data = $this->dataLabels[$data];
        }
    }

}