<?php

namespace SomethingDigital\Migration\Helper\Email;

use Magento\Email\Model\Template as TemplateModel;
use Magento\Email\Model\TemplateFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;
use SomethingDigital\Migration\Exception\UsageException;
use SomethingDigital\Migration\Helper\AbstractHelper;

/**
 * Template helper
 *
 * Extra fields:
 *  - template_subject
 *  - template_styles
 *  - template_type
 *  - template_sender_name
 *  - template_sender_email
 *  - orig_template_code
 *  - orig_template_variables
 */
class Template extends AbstractHelper
{
    protected $date;
    protected $templateFactory;

    public function __construct(DateTime $date, TemplateFactory $templateFactory, StoreManagerInterface $storeManager)
    {
        parent::__construct($storeManager);

        $this->date = $date;
        $this->templateFactory = $templateFactory;
    }

    /**
     * DELETE the original template and create a new one.
     *
     * Used to reset settings.  Consider using update() instead.
     *
     * See class definition for extra fields.
     *
     * @param string $identifier Identifier code.
     * @param string $subject Subject to set.
     * @param string $content Body to set.
     * @param mixed[] $extra Extra fields to set.
     */
    public function replace($identifier, $subject, $content = '', array $extra = [])
    {
        $this->delete($identifier, false);
        $this->create($identifier, $subject, $content, $extra);
    }

    /**
     * Create a new template.
     *
     * See class definition for extra fields.
     *
     * @param string $identifier Identifier code.
     * @param string $subject Subject to set.
     * @param string $content Body to set.
     * @param mixed[] $extra Extra fields to set.
     */
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

    /**
     * Rename a template's subject.
     *
     * @param string $identifier Identifier code.
     * @param string $subject Subject to set.
     * @throws UsageException Template not found for update.
     */
    public function rename($identifier, $subject)
    {
        $template = $this->find($identifier);
        if ($template === null) {
            throw new UsageException(__('Template %1 was not found', $identifier));
        }

        $template->setTemplateSubject($subject);
        $template->save();
    }

    /**
     * Update a template's content or fields.
     *
     * @param string $identifier Identifier code.
     * @param string|null $content Updated body, or null to skip update.
     * @param mixed[] $extra Extra fields to set.
     * @throws UsageException Template not found for update.
     */
    public function update($identifier, $content, array $extra = [])
    {
        $template = $this->find($identifier);
        if ($template === null) {
            throw new UsageException(__('Template %1 was not found', $identifier));
        }

        if ($content !== null) {
            $template->setTemplateText($content);
        }
        $this->setExtra($template, $extra);
        $template->save();
    }

    /**
     * Delete a template.
     *
     * @param string $identifier Identifier code.
     * @param bool $requireExists Whether to fail if it doesn't exist.
     * @throws UsageException Template not found for delete.
     */
    public function delete($identifier, $requireExists = false)
    {
        $template = $this->find($identifier);
        if ($template === null) {
            if ($requireExists) {
                throw new UsageException(__('Template %1 was not found', $identifier));
            }
            return;
        }

        $template->delete();
    }

    /**
     * Set all extra fields on a template instance.
     *
     * Also updated the modified at time.
     *
     * @param TemplateModel $template The template to update.
     * @param string[] $extra Extra field data.
     */
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
