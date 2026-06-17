<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_jsecdash
 */

namespace Joomla\Component\Jsecdash\Administrator\View\Blocks;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Jsecdash\Administrator\Model\BlocksModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Blocks view for com_jsecdash.
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * @var  array
     * @since  1.0.0
     */
    protected $items;

    /**
     * @var  array
     * @since  1.0.0
     */
    protected $attempts;

    /**
     * @param   string  $tpl  The name of the template file to parse.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function display($tpl = null): void
    {
        /** @var BlocksModel $model */
        $model          = $this->getModel();
        $this->items    = $model->getItems();
        $this->attempts = $model->getRecentAttempts();

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

        ToolbarHelper::title(Text::_('COM_JSECDASH_TITLE_BLOCKS'), 'lock');

        $toolbar->linkButton('dashboard', 'COM_JSECDASH_NAV_DASHBOARD')
            ->url('index.php?option=com_jsecdash&view=dashboard')
            ->icon('icon-home');
    }
}
