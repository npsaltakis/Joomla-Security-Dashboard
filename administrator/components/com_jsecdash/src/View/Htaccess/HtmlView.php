<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_jsecdash
 */

namespace Joomla\Component\Jsecdash\Administrator\View\Htaccess;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Jsecdash\Administrator\Model\HtaccessModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Htaccess view for com_jsecdash.
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * @var  string
     * @since  1.0.0
     */
    protected $currentContent;

    /**
     * @var  array
     * @since  1.0.0
     */
    protected $backups;

    /**
     * @param   string  $tpl  The name of the template file to parse.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function display($tpl = null): void
    {
        /** @var HtaccessModel $model */
        $model                 = $this->getModel();
        $this->currentContent  = $model->getCurrentContent();
        $this->backups         = $model->getBackups();

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

        ToolbarHelper::title(Text::_('COM_JSECDASH_TITLE_HTACCESS'), 'file');

        $toolbar->linkButton('dashboard', 'COM_JSECDASH_NAV_DASHBOARD')
            ->url('index.php?option=com_jsecdash&view=dashboard')
            ->icon('icon-home');
    }
}
