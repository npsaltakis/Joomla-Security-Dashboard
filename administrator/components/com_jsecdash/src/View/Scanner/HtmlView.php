<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_jsecdash
 */

namespace Joomla\Component\Jsecdash\Administrator\View\Scanner;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Jsecdash\Administrator\Model\ScannerModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Scanner view for com_jsecdash.
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * @var  array
     * @since  1.0.0
     */
    protected $baselineInfo;

    /**
     * @var  array|null
     * @since  1.0.0
     */
    protected $results;

    /**
     * @var  array
     * @since  1.0.0
     */
    protected $quarantined;

    /**
     * @param   string  $tpl  The name of the template file to parse.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function display($tpl = null): void
    {
        /** @var ScannerModel $model */
        $model              = $this->getModel();
        $this->baselineInfo = $model->getBaselineInfo();
        $this->results      = Factory::getApplication()->getUserState('com_jsecdash.scanner.results');
        $this->quarantined  = $model->getQuarantinedFiles();

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

        ToolbarHelper::title(Text::_('COM_JSECDASH_TITLE_SCANNER'), 'search');

        $toolbar->linkButton('dashboard', 'COM_JSECDASH_NAV_DASHBOARD')
            ->url('index.php?option=com_jsecdash&view=dashboard')
            ->icon('icon-home');
    }
}
