<?php

\defined('_JEXEC') or exit;

use FlexaCms\Component\Flexa\Administrator\View\Dashboard\HtmlView;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var HtmlView $this */
?>
<div class="row">
    <div class="col-lg-8">
        <?php if (! $this->configured) { ?>
            <div class="alert alert-info">
                <?php echo Text::_('COM_FLEXA_NOT_CONFIGURED'); ?>
                <a href="<?php echo Route::_('index.php?option=com_config&view=component&component=com_flexa'); ?>"><?php echo Text::_('JTOOLBAR_OPTIONS'); ?></a>
            </div>
        <?php } elseif ($this->error !== null) { ?>
            <div class="alert alert-danger">
                <strong><?php echo Text::_('COM_FLEXA_CONNECTION_FAILED'); ?></strong>
                <?php echo $this->escape($this->error); ?>
            </div>
        <?php } else { ?>
            <div class="alert alert-success"><?php echo Text::plural('COM_FLEXA_CONNECTED_N_MODELS', count($this->entities)); ?></div>

            <table class="table">
                <thead>
                    <tr>
                        <th scope="col"><?php echo Text::_('COM_FLEXA_CONTENT_MODEL'); ?></th>
                        <th scope="col"><?php echo Text::_('COM_FLEXA_SLUG'); ?></th>
                        <th scope="col"><?php echo Text::_('COM_FLEXA_ARTICLE_TAG'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->entities as $entity) { ?>
                        <tr>
                            <td><?php echo $this->escape((string) ($entity['name'] ?? '')); ?></td>
                            <td><code><?php echo $this->escape((string) ($entity['slug'] ?? '')); ?></code></td>
                            <td><code>{flexa entity="<?php echo $this->escape((string) ($entity['slug'] ?? '')); ?>"}</code></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

            <form action="<?php echo Route::_('index.php?option=com_flexa&task=display.clearCache'); ?>" method="post">
                <button type="submit" class="btn btn-secondary"><?php echo Text::_('COM_FLEXA_CLEAR_CACHE'); ?></button>
                <?php echo HTMLHelper::_('form.token'); ?>
            </form>
        <?php } ?>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h2 class="card-title h5"><?php echo Text::_('COM_FLEXA_HOW_TO'); ?></h2>
                <ul class="mb-0">
                    <li><?php echo Text::_('COM_FLEXA_HOW_TO_MENU'); ?></li>
                    <li><?php echo Text::_('COM_FLEXA_HOW_TO_MODULE'); ?></li>
                    <li><?php echo Text::_('COM_FLEXA_HOW_TO_TAG'); ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>
