<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_jsecdash
 */

namespace Joomla\Component\Jsecdash\Administrator\View\Dashboard;

use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Jsecdash\Administrator\Model\DashboardModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Dashboard view for com_jsecdash.
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * @var  array
     * @since  1.0.0
     */
    protected $stats;

    /**
     * @var  string
     * @since  1.0.2
     */
    protected $version = '';

    /**
     * @param   string  $tpl  The name of the template file to parse.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function display($tpl = null): void
    {
        /** @var DashboardModel $model */
        $model       = $this->getModel();
        $this->stats = $model->getStats();

        $manifest      = simplexml_load_file(JPATH_ADMINISTRATOR . '/components/com_jsecdash/jsecdash.xml');
        $this->version = $manifest ? (string) $manifest->version : '';

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
        $canDo   = ContentHelper::getActions('com_jsecdash');
        $toolbar = $this->getDocument()->getToolbar();

        ToolbarHelper::title(Text::_('COM_JSECDASH_TITLE_DASHBOARD'), 'shield-alt');

        $toolbar->linkButton('healthcheck', 'COM_JSECDASH_NAV_HEALTHCHECK')
            ->url('index.php?option=com_jsecdash&view=healthcheck')
            ->icon('icon-health');

        $toolbar->linkButton('blocks', 'COM_JSECDASH_NAV_BLOCKS')
            ->url('index.php?option=com_jsecdash&view=blocks')
            ->icon('icon-lock');

        $toolbar->linkButton('waf', 'COM_JSECDASH_NAV_WAF')
            ->url('index.php?option=com_jsecdash&view=waf')
            ->icon('icon-shield');

        $toolbar->linkButton('htaccess', 'COM_JSECDASH_NAV_HTACCESS')
            ->url('index.php?option=com_jsecdash&view=htaccess')
            ->icon('icon-file');

        $toolbar->linkButton('scanner', 'COM_JSECDASH_NAV_SCANNER')
            ->url('index.php?option=com_jsecdash&view=scanner')
            ->icon('icon-search');

        $toolbar->linkButton('auditlog', 'COM_JSECDASH_NAV_AUDITLOG')
            ->url('index.php?option=com_jsecdash&view=auditlog')
            ->icon('icon-list');

        $toolbar->linkButton('backup', 'COM_JSECDASH_NAV_BACKUP')
            ->url('index.php?option=com_jsecdash&view=backup')
            ->icon('icon-database');

        if ($canDo->get('core.admin') || $canDo->get('core.options')) {
            $toolbar->preferences('com_jsecdash');
        }
    }
}
