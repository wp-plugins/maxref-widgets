<?= $args['before_widget']; ?>
	<?= $args['before_title']; ?><?= (!empty($args['titlelink']) && $args['titlelink'] == "Y") ? '<a href="' . $args['titlelinkurl'] . '" title="' . $args['title'] . '">' : ''; ?><?= $args['title']; ?><?= (!empty($args['titlelink']) && $args['titlelink'] == "Y") ? '</a>' : ''; ?><?= $args['after_title']; ?>
	<?php if (!empty($args['parent'])) : ?>
		<ul>
			<li>
				<a href="<?= $args['parent']['href']; ?>" title="<?= $args['parent']['title']; ?>"><?= $args['parent']['title']; ?></a>
	<?php endif; ?>
	<ul>
		<?php foreach ($items as $item) : 
		
		
		?>
			<li>
				<?php if (!empty($item['rsslink']) && $item['rsslink'] == true) : ?>
					
				<?php if(!empty($item['post_ID'])) { ?>					
							<a class="rsswidget" href="<?= get_option('home'); ?>/?feed=rss2&amp;p=<?= $item['post_ID']; ?>&withoutcomments=1" title="<?php _e('RSS Feed', $this -> plugin_name); ?>"><img style="border:none !important;" src="<?= get_option('siteurl'); ?>/wp-includes/images/rss.png" alt="<?php _e('rss', $this -> plugin_name); ?>" />
				<?php } 
					if (!empty($item['page_ID'])) { ?>					
					  <a class="rsswidget" href="<?= get_option('home'); ?>/?feed=rss2&amp;page_id=<?= $item['page_ID']; ?>&withoutcomments=1" title="<?php _e('RSS Feed', $this -> plugin_name); ?>"><img style="border:none !important;" src="<?= get_option('siteurl'); ?>/wp-includes/images/rss.png" alt="<?php _e('rss', $this -> plugin_name); ?>" />
			  <?php } else if(!empty($item['comment_ID'])) { ?>	                    				
						<a class="rsswidget" href="<?= get_option('home'); ?>/?feed=rss2&amp;p=<?= $item['post_ID']; ?>" title="<?php _e('RSS Feed', $this -> plugin_name); ?>">
		 	 <?php } else if(!empty($item['cat_ID'])) { ?>					
						<a class="rsswidget" href="<?= get_option('home'); ?>/?feed=rss2&amp;cat=<?= $item['cat_ID']; ?>" title="<?php _e('RSS Feed', $this -> plugin_name); ?>">
                    <img style="border:none !important;" src="<?= get_option('siteurl'); ?>/wp-includes/images/rss.png" alt="<?php _e('rss', $this -> plugin_name); ?>" />
			<?php }?>
					</a>
					
				<?php endif; ?>
                
				<a href="<?= $item['href']; ?>" title="<?= $item['title']; ?>"><?= (!empty($args['max_length']) && strlen($item['title']) > $args['max_length']) ? substr($item['title'], 0, $args['max_length']) . '...' : $item['title']; ?></a>
				<?php if (!empty($item['description']) && !empty($args['linkdescriptions']) && $args['linkdescriptions'] == "Y") : ?>
					<br/><small><?= $item['description']; ?></small>
				<?php endif; ?>
				<?php if (!empty($item['date']) && !empty($args['itemdates']) && $args['itemdates'] == "Y") : ?>
					<br/><small><?= $item['date']; ?></small>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php if (!empty($args['parent'])) : ?>
			</li>
		</ul>
	<?php endif; ?>

	<?php if (!empty($this -> adversion) && $this -> adversion == true) : ?>	
		<p class="<?= $this -> pre; ?>ad">
			<small>MaxRef by <a href="http://www.webfadds.com" title="WebFadds">WebFadds.com</a></small>
		</p>
	<?php endif; ?>
<?= $args['after_widget']; ?>