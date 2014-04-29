<?php

namespace Oro\Bundle\ChartBundle\Form\Type;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\ChartBundle\Exception\InvalidArgumentException;

class ChartType extends ConfigProviderAwareType
{
    /**
     * @var array
     */
    protected $optionsGroups = ['settings', 'data_schema'];

    /**
     * @var EventSubscriberInterface
     */
    protected $eventListener;

    /**
     * @param EventSubscriberInterface $eventListener
     */
    public function setEventListener(EventSubscriberInterface $eventListener)
    {
        $this->eventListener = $eventListener;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->eventListener);

        $chartConfigs = $this->getChartConfigs($options);

        $builder
            ->add(
                'name',
                'choice',
                [
                    'choices' => array_map(
                        function (array $chartConfig) {
                            return $chartConfig['label'];
                        },
                        $chartConfigs
                    ),
                    'empty_value' => 'oro.chart.form.chart_empty_value'
                ]
            )
            ->add('settings', 'oro_chart_settings_collection', ['chart_configs' => $chartConfigs]);

        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'submit']);
    }

    /**
     * @param array $options
     * @return array
     */
    protected function getChartConfigs(array $options)
    {
        $result = $this->configProvider->getChartConfigs();

        if (isset($options['chart_filter'])) {
            $result = array_filter($result, $options['chart_filter']);
        }

        return $result;
    }

    /**
     * @param FormEvent $event
     * @throws InvalidArgumentException
     */
    public function submit(FormEvent $event)
    {
        $formData = $event->getData();

        if (!isset($formData['name'])) {
            $event->setData(array());
            return;
        }

        $name = $formData['name'];

        if (isset($formData['settings'][$name])) {
            $formData['settings'] = $formData['settings'][$name];
        }

        if (isset($formData['data_schema'][$name])) {
            $formData['data_schema'] = $formData['data_schema'][$name];
        }

        $event->setData($formData);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(['chart_filter']);
        $resolver->setAllowedTypes(['chart_filter' => 'callable']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_chart';
    }
}
