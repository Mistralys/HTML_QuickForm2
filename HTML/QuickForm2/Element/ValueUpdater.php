<?php

declare(strict_types=1);

namespace HTML\QuickForm2\Element;

use HTML_QuickForm2_DataSource_NullAware;
use HTML_QuickForm2_Node;

class ValueUpdater
{
    private HTML_QuickForm2_Node $element;

    public function __construct(HTML_QuickForm2_Node $element)
    {
        $this->element = $element;
    }

    public function update() : self
    {
        $name = $this->element->getName();
        $dataSources = $this->element->getDataSources();

        $this->element->log('Updating value from [%s] data sources.', count($dataSources));

        foreach ($dataSources as $ds)
        {
            $value = $ds->getValue($name);

            if (
                $value !== null
                ||
                ($ds instanceof HTML_QuickForm2_DataSource_NullAware && $ds->hasValue($name))
            )
            {
                $this->element->setValue($value);
                return $this;
            }
        }

        return $this;
    }
}
