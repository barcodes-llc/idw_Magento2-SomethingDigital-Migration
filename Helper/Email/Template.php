<?php

namespace SomethingDigital\Migration\Helper\Email;

use Magento\Email\Model\Template as TemplateModel;
use Magento\Email\Model\TemplateFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;
use SomethingDigital\Migration\Exception\UsageException;

class Template
{
    protected $date;
    protected $templateFactory;

    public function __construct(DateTime $date, TemplateFactory $templateFactory)
    {
        $this->date = $date;
        $this->templateFactory = $templateFactory;
    }

    public function replace($identifier, $subject, $content = '', array $extra = [])
    {
        $this->delete($identifier, false);
        $this->create($identifier, $subject, $content, $extra);
    }

    public function create($identifier, $subject, $content = '', array $extra = [])
    {
        /** @var TemplateModel $template */
        $template = $this->templateFactory->create();
        $template->setTemplateCode($identifier);
        $template->setTemplateSubject($subject);
        $template->setTemplateText($content);
        $this->setExtra($template, $extra);
        $template->setAddedAt($this->date->gmtDate());

        $template->save();
    }

    public function rename($identifier, $subject)
    {
        $template = $this->find($identifier);
        if ($template === null) {
            throw new UsageException(__('Template %s was not found', $identifier));
        }

        $template->setTemplateSubject($subject);
        $template->save();
    }

    public function update($identifier, $content, array $extra = [])
    {
        $template = $this->find($identifier);
        if ($template === null) {
            throw new UsageException(__('Template %s was not found', $identifier));
        }

        if ($content !== null) {
            $template->setTemplateText($content);
        }
        $this->setExtra($template, $extra);
        $template->save();
    }

    public function delete($identifier, $requireExists = false)
    {
        $template = $this->find($identifier);
        if ($template === null) {
            if ($requireExists) {
                throw new UsageException(__('Template %s was not found', $identifier));
            }
            return;
        }

        $template->delete();
    }

    protected function setExtra(TemplateModel $template, array $extra)
    {
        // We can use setData ATM, but Magento is moving away from that API.
        $fields = [
            'template_subject' => 'setTemplateSubject',
            'template_styles' => 'setTemplateStyles',
            'template_type' => 'setTemplateType',
            'template_sender_name' => 'setTemplateSenderName',
            'template_sender_email' => 'setTemplateSenderEmail',
            'orig_template_code' => 'setOrigTemplateCode',
            'orig_template_variables' => 'setOrigTemplateVariables',
        ];

        foreach ($fields as $field => $setter) {
            if (isset($extra[$field])) {
                $template->$setter($extra[$field]);
            }
        }

        $template->setModifiedAt($this->date->gmtDate());
    }

    /**
     * Find a template for update or delete.
     *
     * @param string $identifier Template text identifier.
     * @return TemplateModel|null
     */
    protected function find($identifier)
    {
        /** @var TemplateModel $template */
        $template = $this->templateFactory->create();
        $template->load($identifier, 'template_code');

        if (!$template->getId()) {
            return null;
        }

        return $template;
    }
}
