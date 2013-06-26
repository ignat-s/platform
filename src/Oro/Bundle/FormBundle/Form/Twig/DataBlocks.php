<?php

namespace Oro\Bundle\FormBundle\Form\Twig;

use Oro\Bundle\FormBundle\Config\SubBlockConfig;
use Symfony\Component\Form\FormView;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\FormBundle\Config\BlockConfig;
use Oro\Bundle\FormBundle\Config\FormConfig;
use Oro\Bundle\FormBundle\Exception\RuntimeException;


class DataBlocks
{
    /**
     * @var FormConfig
     */
    protected $formConfig;

    /**
     * @var string
     */
    protected $formVariableName;

    /**
     * @var mixed
     */
    protected $context;

    /**
     * @var \Twig_Environment
     */
    protected $env;

    /**
     * @var PropertyAccessor
     */
    protected $accessor;

    public function __construct()
    {
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * @param \Twig_Environment $env
     * @param                   $context
     * @param FormView          $form
     * @param string            $formVariableName
     * @return array
     */
    public function render(\Twig_Environment $env, $context, FormView $form, $formVariableName = 'form')
    {
        $this->formVariableName = $formVariableName;
        $this->formConfig       = new FormConfig;
        $this->context          = $context;
        $this->env              = $env;

        $tmpLoader = $env->getLoader();
        $env->setLoader(new \Twig_Loader_String());

        try {

            $this->renderBlock($form);
        } catch (\Exception $e) {
            var_dump($e);
            die;
        }

        $env->setLoader($tmpLoader);

        return $this->formConfig->toArray();
    }

    /**
     * @param FormView $form
     */
    protected function renderBlock(FormView $form)
    {
        if (isset($form->vars['block_config'])) {
            foreach ($form->vars['block_config'] as $code => $blockConfig) {
                $this->createBlock($code, $blockConfig);
            }
        }

        foreach ($form->children as $name => $child) {
            if (isset($child->vars['block']) || isset($child->vars['subblock'])) {

                $block = null;
                if (isset($child->vars['block']) && $this->formConfig->hasBlock($child->vars['block'])) {
                    $block = $this->formConfig->getBlock($child->vars['block']);
                } else if (!isset($child->vars['block'])) {
                    $blocks = $this->formConfig->getBlocks();
                    $block  = reset($blocks);
                }

                if (!$block) {
                    $blockCode = isset($child->vars['block'])
                        ? $child->vars['block']
                        : $name;

                    $block = $this->createBlock($blockCode);

                    $this->formConfig->addBlock($block);
                }

                $subBlock = null;
                if (isset($child->vars['subblock']) && $block->hasSubBlock($child->vars['subblock'])) {
                    $subBlock = $block->getSubBlock($child->vars['subblock']);
                } else if (!isset($child->vars['subblock'])) {
                    $subBlocks = $block->getSubBlocks();
                    $subBlock  = reset($subBlocks);
                }

                if (!$subBlock) {
                    $subBlockCode = isset($child->vars['subblock'])
                        ? $child->vars['subblock']
                        : $name . '__subblock';

                    $subBlock = $this->createSubBlock($subBlockCode, array('title' => ''));

                    $block->addSubBlock($subBlock);
                }

                $tmpChild = $child;
                $formPath = '';

                while ($tmpChild->parent) {
                    $formPath = sprintf('.children[\'%s\']', $tmpChild->vars['name']) . $formPath;
                    $tmpChild = $tmpChild->parent;
                }

                $subBlock->addData($this->env->render(
                    '{{ form_row(' . $this->formVariableName . $formPath . ') }}',
                    $this->context
                ));
            }

            $this->renderBlock($child);
        }
    }


    protected function createBlock($code, $blockConfig = array())
    {
        if ($this->formConfig->hasBlock($code)) {
            throw new RuntimeException(sprintf("block_config '%s' isset in form config.", $code));
        }

        $block = new BlockConfig($code);
        $block->setClass($this->accessor->getValue($blockConfig, '[class]'));
        $block->setPriority($this->accessor->getValue($blockConfig, '[priority]'));

        $title = $this->accessor->getValue($blockConfig, '[title]')
            ? $this->accessor->getValue($blockConfig, '[title]')
            : ucfirst($code);
        $block->setTitle($title);

        foreach ((array)$this->accessor->getValue($blockConfig, '[subblocks]') as $subCode => $subBlockConfig) {
            $block->addSubBlock($this->createSubBlock($subCode, (array)$subBlockConfig));
        }

        $this->formConfig->addBlock($block);

        return $block;
    }

    /**
     * @param $code
     * @param $config
     * @return SubBlockConfig
     */
    protected function createSubBlock($code, $config)
    {
        $subBlock = new SubBlockConfig($code);
        $subBlock->setTitle($this->accessor->getValue($config, '[title]'));
        $subBlock->setPriority($this->accessor->getValue($config, '[priority]'));

        return $subBlock;
    }
}