<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Common\Cache\Cache;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class EmailRenderer extends \Twig_Environment
{
    /** @var  ConfigProvider */
    protected $configProvider;

    /** @var VariablesProvider */
    protected $variablesProvider;

    /** @var  Cache|null */
    protected $sandBoxConfigCache;

    /** @var  string */
    protected $cacheKey;

    public function __construct(
        \Twig_LoaderInterface $loader,
        $options,
        ConfigProvider $configProvider,
        VariablesProvider $variablesProvider,
        Cache $cache,
        $cacheKey,
        \Twig_Extension_Sandbox $sandbox
    ) {
        parent::__construct($loader, $options);

        $this->configProvider     = $configProvider;
        $this->variablesProvider  = $variablesProvider;
        $this->sandBoxConfigCache = $cache;
        $this->cacheKey           = $cacheKey;

        $this->addExtension($sandbox);
        $this->configureSandbox();
    }

    /**
     * Configure sandbox form config data
     *
     */
    protected function configureSandbox()
    {
        $allowedData = $this->sandBoxConfigCache->fetch($this->cacheKey);

        if (false === $allowedData) {
            $allowedData = $this->prepareConfiguration();
            $this->sandBoxConfigCache->save($this->cacheKey, serialize($allowedData));
        } else {
            $allowedData = unserialize($allowedData);
        }

        /** @var \Twig_Extension_Sandbox $sandbox */
        $sandbox = $this->getExtension('sandbox');
        /** @var \Twig_Sandbox_SecurityPolicy $security */
        $security = $sandbox->getSecurityPolicy();
        $security->setAllowedProperties($allowedData['properties']);
        $security->setAllowedMethods($allowedData['methods']);
    }

    /**
     * Prepare configuration from entity config
     *
     * @return array
     */
    private function prepareConfiguration()
    {
        $configuration = array();

        foreach ($this->configProvider->getIds() as $entityConfigId) {
            $className = $entityConfigId->getClassName();

            $properties = [];
            $methods    = [];
            $getters    = $this->variablesProvider->getEntityVariableGetters($className);
            foreach ($getters as $varName => $getter) {
                if (empty($getter)) {
                    $properties[] = $varName;
                } else {
                    $methods[] = $getter;
                }
            }

            $configuration['properties'][$className] = $properties;
            $configuration['methods'][$className]    = $methods;
        }

        return $configuration;
    }

    /**
     * Compile email message
     *
     * @param EmailTemplateInterface $template
     * @param array                  $templateParams
     *
     * @return array first element is email subject, second - message
     */
    public function compileMessage(EmailTemplateInterface $template, array $templateParams = array())
    {
        // ensure we have no html tags in txt template
        $content = $template->getContent();
        $content = $template->getType() == 'txt' ? strip_tags($content) : $content;

        $templateParams['system'] = $this->variablesProvider->getSystemVariableValues();

        $templateRendered = $this->render($content, $templateParams);
        $subjectRendered  = $this->render($template->getSubject(), $templateParams);

        return array($subjectRendered, $templateRendered);
    }

    /**
     * Compile preview content
     *
     * @param EmailTemplate $entity
     *
     * @return string
     */
    public function compilePreview(EmailTemplate $entity)
    {
        // ensure we have no html tags in txt template
        $content = $entity->getContent();
        $content = $entity->getType() == 'txt' ? strip_tags($content) : $content;

        $templateRendered = $this->render('{% verbatim %}' . $content . '{% endverbatim %}', []);

        return $templateRendered;
    }
}
