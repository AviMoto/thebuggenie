<div class="article">
    <div class="header">Special:<?php echo ($projectnamespace != '') ? "<span class='faded_out'>{$projectnamespace}</span>" : ''; ?>Orphaned Pages</div>
    <?php if (TBGContext::isProjectContext()): ?>
        <div class="greybox" style="margin: 15px;">
            <?php echo __('Note: This page lists all articles that are not linked to for "%project_name". For a list of global articles that are not linked to, see %orphaned_pages', array('%project_name' => TBGContext::getCurrentProject()->getName(), '%orphaned_pages' => link_tag(make_url('publish_article', array('article_name' => "Special:OrphanedPages")), 'Special:OrphanedPages'))); ?>
        </div>
    <?php endif; ?>
    <p>
        <?php echo __('Below is a listing of pages that are not linked to by other pages.'); ?>
    </p>
    <?php include_template('publish/articleslist', array('articles' => $articles, 'include_redirects' => false)); ?>
</div>
