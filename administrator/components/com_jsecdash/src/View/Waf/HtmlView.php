<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_jsecdash
 */

namespace Joomla\Component\Jsecdash\Administrator\View\Waf;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Jsecdash\Administrator\Model\WafModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Web Application Firewall view for com_jsecdash.
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * @var  string
     * @since  1.0.0
     */
    protected $mode;

    /**
     * @var  array
     * @since  1.0.0
     */
    protected $summary;

    /**
     * @var  array
     * @since  1.0.0
     */
    protected $categories;

    /**
     * @var  array
     * @since  1.0.0
     */
    protected $topRules;

    /**
     * @var  array
     * @since  1.0.0
     */
    protected $items;

    /**
     * @param   string  $tpl  The name of the template file to parse.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function display($tpl = null): void
    {
        /** @var WafModel $model */
        $model            = $this->getModel();
        $this->mode       = $model->getMode();
        $this->summary    = $model->getSummary();
        $this->categories = $model->getCategoryBreakdown();
        $this->topRules   = $model->getTopRules();
        $this->items      = $model->getItems();

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * @return  void
     *
     * @since   1.0.0
     */
    protected function addToolbar(): void
    {
        $toolbar = $this->getDocument()->getToolbar();

        ToolbarHelper::title(Text::_('COM_JSECDASH_TITLE_WAF'), 'shield');

        $toolbar->linkButton('dashboard', 'COM_JSECDASH_NAV_DASHBOARD')
            ->url('index.php?option=com_jsecdash&view=dashboard')
            ->icon('icon-home');
    }
}
