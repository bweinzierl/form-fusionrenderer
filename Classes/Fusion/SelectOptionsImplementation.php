<?php
namespace Neos\Form\FusionRenderer\Fusion;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Translator;
use Neos\Flow\ResourceManagement\Exception as ResourceException;
use Neos\Form\Core\Model\FormElementInterface;
use Neos\Fusion\Exception as FusionException;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

class SelectOptionsImplementation extends AbstractFusionObject
{

    /**
     * @Flow\Inject
     * @var Translator
     */
    protected $translator;

    public function evaluate()
    {
        $context = $this->runtime->getCurrentContext();
        if (!isset($context['element'])) {
            throw new FusionException('Missing "element" in the Fusion context', 1502108256);
        }
        $element = $context['element'];
        if (!$element instanceof FormElementInterface) {
            throw new FusionException(sprintf('SelectOptions object can only be used within FormElementInterface elements, given: %s', is_object($element) ? get_class($element) : gettype($element)), 1502108340);
        }
        if (!isset($element->getProperties()['options'])) {
            throw new FusionException('The current element doesn\'t have an "options" property', 1502108512);
        }
        $output = '';
        foreach ($element->getProperties()['options'] as $optionValue => $originalLabel) {
            if ($originalLabel === null) {
                $originalLabel = '';
            }
            $translationId = sprintf('forms.elements.%s.options.%s', $element->getIdentifier(), $optionValue);
            $optionLabel = htmlspecialchars($this->translate($element, $translationId, $originalLabel), ENT_QUOTES);
            $context['optionValue'] = $optionValue;
            $context['optionLabel'] = $optionLabel;
            $context['optionSelected'] = $this->isOptionSelected($optionValue);

            $this->runtime->pushContextArray($context);
            $output .= $this->runtime->render($this->path . '/itemRenderer');
            $this->runtime->popContext();
        }
        return $output;
    }

    public function translate(FormElementInterface $element, string $translationId, string $defaultValue): string
    {
        $renderingOptions = $element->getRenderingOptions();
        if (!isset($renderingOptions['translationPackage'])) {
            return $defaultValue;
        }
        try {
            $translation = $this->translator->translateById($translationId, [], null, null, 'Main', $renderingOptions['translationPackage']);
        } catch (ResourceException $exception) {
            return $defaultValue;
        }
        return $translation ?? $defaultValue;
    }

    private function isOptionSelected($optionValue): bool
    {
        $elementValue = ($this->runtime->getCurrentContext())['elementValue'] ?? null;
        if ($optionValue === $elementValue) {
            return true;
        }
        if (is_array($elementValue) && in_array($optionValue, $elementValue)) {
            return true;
        }
        return false;
    }
}