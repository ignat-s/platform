<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class ConfigType extends AbstractType
{
    protected $provider;

    public function __construct(ConfigProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($this->provider->getConfigContainer()->getEntityItems() as $code => $config) {
            if (isset($config['form']) && isset($config['form']['type']) && isset($config['form']['options'])) {
                $config['form']['options']['block']    = $this->provider->getScope() . (isset($config['form']['block'])
                        ? $config['form']['block']
                        : 0
                    );
                $config['form']['options']['subblock'] = isset($config['form']['subblock'])
                    ? $config['form']['subblock']
                    : 0;


                $builder->add($code, $config['form']['type'], $config['form']['options']);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_entity_config_config_type';
    }
}