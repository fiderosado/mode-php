<?php

namespace Core\Html;

use Core\SuspenseAction;

class Suspense extends HtmlElement
{
    protected SuspenseAction $action;
    protected string $hash;
    protected string $actionName;

    /**
     * El tag será un div contenedor
     */
    protected function getTagName(): string
    {
        return 'div';
    }

    /**
     * Constructor compatible con HtmlElement moderno
     */
    public function __construct($fallback, SuspenseAction $action)
    {
        parent::__construct($fallback);
        $this->action = $action;
        $this->hash = $action->getHash();
        $this->actionName = $action->getName() ?? '';
    }

    /**
     * Sobrescribimos atributos para agregar los data-* necesarios
     */
    protected function getAttributes(): string
    {
        $payload = $this->action->getPayload();
        $payloadJson = json_encode($payload);
        $payloadBase64 = base64_encode($payloadJson);
        return parent::getAttributes()
            . ' data-suspense="' . htmlspecialchars($this->hash) . '"'
            . ' data-action="' . htmlspecialchars($this->actionName) . '"'
            . ' data-target="' . htmlspecialchars($this->getDataId()) . '"'
            . ' data-payload="' . $payloadBase64 . '"';
    }
}
