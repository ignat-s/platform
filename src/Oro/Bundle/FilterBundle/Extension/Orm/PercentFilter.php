<?php

namespace Oro\Bundle\FilterBundle\Extension\Orm;

use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;

class PercentFilter extends NumberFilter
{
    /**
     * {@inheritDoc}
     */
    public function init($name, array $params)
    {
        $params[self::FRONTEND_TYPE_KEY]             = 'number';
        $params[self::FORM_OPTIONS_KEY]              = isset($params[self::FORM_OPTIONS_KEY])
            ? $params[self::FORM_OPTIONS_KEY] : [];
        $params[self::FORM_OPTIONS_KEY]['data_type'] = NumberFilterType::DATA_DECIMAL;
        parent::init($name, $params);
    }

    /**
     * {@inheritdoc}
     */
    public function parseData($data)
    {
        $data = parent::parseData($data);

        if ($data && is_numeric($data['value'])) {
            $data['value'] /= 100;
        }

        return $data;
    }
}
