<?php

/*
Plugin Name: Maxref Widgets
Plugin URI: http://www.webfadds.com/plugins/
Author: WebFadds
Author URI: http://webfadds.com
Description: Display multiple sidebar widgets to maximize how your visitors reference your posts, links, categories and comments
Version: 1.9.1
*/

require_once(dirname(__FILE__) . '/maxref-widgets-plugin.php');

class mrefWidgets extends mrefWidgetsPlugin {

	var $name = 'maxref-widgets';
	
	function mrefWidgets() {
		$url = explode("&", $_SERVER['REQUEST_URI']);
		$this -> url = $url[0];
	
		$this -> register_plugin($this -> name, __FILE__);
		$this -> initialize_options();	
		
		$this -> add_action('widgets_init', 'mref_widget_register', 10, 1);
		$this -> add_action('admin_head');
		$this -> add_action('wp_head');
		
		return true;
	}
	
	function mref_widget($args = array(), $widget_args = array()) {
		extract($args, EXTR_SKIP);
		
		if (is_numeric($widget_args)) {
			$widget_args = array('number' => $widget_args);
		}
			
		$widget_args = wp_parse_args($widget_args, array('number' => -1));
		extract($widget_args, EXTR_SKIP);
	
		$options = get_option('mref-widget');		
		if (empty($options[$number])) {
			return;
		}
		
		$title = $options[$number]['title'];
		$recent = $options[$number]['recent'];
		$orderby = $options[$number]['orderby'];
		$order = $options[$number]['order'];
		$max_length = $options[$number]['max_length'];
		$exclude = $options[$number]['exclude'];
		$numberitems = $options[$number]['numberitems'];
		$rotateposts = $options[$number]['rotateposts'];
		$linkdescriptions = $options[$number]['linkdescriptions'];
		$catrsslinks = $options[$number]['catrsslinks'];
		$itemdates = $options[$number]['itemdates'];
		$dateformat = $options[$number]['dateformat'];
		$pagesparent = $options[$number]['pagesparent'];
		$hide_empty = $options[$number]['hide_empty'];
		$titlelink = $options[$number]['titlelink'];
		$titlelinkurl = $options[$number]['titlelinkurl'];
		$levels = $options[$number]['levels'];
		
		if (preg_match("%^([posts|pages]*)\-.*?$%si", $recent, $matches)) {		
			$post_type = rtrim($matches[1], 's');
			
			if ($post_type == "post") {
				preg_match("%^posts\-(.*?)$%si", $recent, $matches);
				$category = $matches[1];
				$newcategory = (empty($category) || $category == "all") ? false : $category;
				
				//should posts be rotated by category?
				if (!empty($rotateposts) && $rotateposts == "Y") {					
					$category_args = array(
						'type'					=>	"post",
						'number'				=>	false,
						'orderby'				=>	"name",
						'order'					=>	"ASC",
						'hide_empty'			=>	true,
					);
					
					$currid = 0;
					
					if ($categories = get_categories($category_args)) {					
						//check if a category's posts has been rotated
						if (${'rotateposts' . $number} = $this -> get_option('rotateposts' . $number)) {
							
							if (!empty($categories[${'rotateposts' . $number}])) {
								$newcategory = $categories[${'rotateposts' . $number}] -> cat_ID;
								$currid = ${'rotateposts' . $number};
							} else {
								$newcategory = $categories[0] -> cat_ID;
							}
						} else {
							$newcategory = $categories[0] -> cat_ID;
						}
					}
					
					$this -> update_option('rotateposts' . $number, ($currid + 1));
				}
				
				//WP post arguments for get_posts()
				$post_args = array(
					'numberposts'		=>	(empty($numberitems)) ? false : $numberitems,
					'category'			=>	$newcategory,
					'orderby'			=>	(empty($orderby) || $orderby == "name") ? 'title' : 'date',
					'order'				=>	(empty($order) || $order == "ASC") ? 'ASC' : 'DESC',
					'exclude'			=>	(empty($exclude)) ? false : $exclude,
					'post_type'			=>	$post_type
				);
				
				if ($posts = get_posts($post_args)) {
					$items = array();
				
					foreach ($posts as $post) {
						$items[] = array(
							'title'			=>	$post -> post_title,
							'href'			=>	get_permalink($post -> ID),
							'date'			=>	date($dateformat, strtotime($post -> post_date)),
						);
					}
				}
			} elseif ($post_type == "page") {
				preg_match("%^pages\-(.*?)$%si", $recent, $matches);
				$child_of = $matches[1];
				
				if (!empty($child_of) && $child_of != "all" && $child_of != 0 && $pagesparent == "Y") {
					$parent = get_page($child_of);
					$args['parent']['title'] = $parent -> post_title;
					$args['parent']['href'] = get_permalink($parent -> ID);
				}
				
				$page_args = array(
					'child_of'			=>	(empty($child_of) || $child_of == "all") ? 0 : $child_of,
					'sort_order'		=>	(empty($order) || $order == "ASC") ? 'ASC' : 'DESC',
					'sort_column'		=>	(empty($orderby) || $orderby == "name") ? 'post_title' : 'post_date',
					'exclude'			=>	(empty($exclude)) ? false : $exclude
				);
				
				global $wpdb;
				
				$page_query = "SELECT `ID`, `post_title` FROM `" . $wpdb -> posts . "`";
				$page_query .= (empty($child_of) || $child_of == "all") ? " WHERE `post_type` = 'page'" : " WHERE `post_type` = 'page' AND `post_parent` = '" . $child_of . "'";
				$order = (empty($order) || $order == "ASC") ? 'ASC' : 'DESC';
				$orderby = (empty($orderby) || $orderby == "name") ? 'post_title' : 'post_date';
				$page_query .= " ORDER BY `" . $orderby . "` " . $order . "";
				
				if ($pages = $wpdb -> get_results($page_query)) {
					global $items, $levels, $usedpages;
				
					$items = array();
					$levels = $options[$number]['levels'];
					$usedpages = array();
					
					foreach ($pages as $page) {
						$items[] = array(
							'title'			=>	$page -> post_title,
							'href'			=>	get_permalink($page -> ID),
							'date'			=>	date($dateformat, strtotime($page -> post_date)),
						);
						
						$childargs = array(
							'child_of'			=>	$page -> ID,
							'order'				=>	$order,
							'orderby'			=>	$orderby,
							'exclude'			=>	$exclude,
						);
						
						$usedpages[] = $page -> ID;					
						$this -> get_pages($childargs);
					}
				}
			}
		} elseif (ereg("categories", $recent)) {		
			preg_match("%^categories\-(.*?)$%i", $recent, $matches);
			$parent_id = $matches[1];
			
			if (!empty($parent_id) && $parent_id != "all" && $parent_id != 0 && $pagesparent == "Y") {
				$parent = get_category($parent_id);
				$args['parent']['title'] = $parent -> cat_name;
				$args['parent']['href'] = get_category_link($parent -> cat_ID);
				$allcategories = false;
			} else {
				$allcategories = true;
			}
			
			$category_args = array(
				'number'			=>	(empty($numberitems)) ? false : $numberitems,
				'parent'			=>	($parent_id == "all") ? false : $parent_id,
				'order'				=>	$order,
				'orderby'			=>	(empty($orderby) || $orderby != "date") ? 'name' : 'ID',
				'exclude'			=>	(empty($exclude)) ? false : $exclude,
				'hide_empty'		=>	(empty($hide_empty) || $hide_empty == "Y") ? true : false,
			);
			
			if ($categories = get_categories($category_args)) {
				global $items, $levels;
				
				$items = array();
				$levels = $options[$number]['levels'];
				$c = 0;
				
				foreach ($categories as $category) {				
					$items[$c] = array(
						'title'			=>	$category -> cat_name,
						'href'			=>	get_category_link($category -> cat_ID),
						'cat_ID'		=>	$category -> cat_ID,
					);
					
					$childargs = array(
						'parent'		=>	$category -> cat_ID,
						'order'			=>	$order,
						'orderby'		=>	(empty($orderby) || $orderby != "date") ? 'name' : 'ID',
						'exclude'		=>	(empty($exclude)) ? false : $exclude,
						'hide_empty'	=>	true,
					);
					
					$this -> get_categories($childargs);
					$c++;
				}
			}
		} elseif (ereg("links", $recent)) {
			preg_match("%^links\-(.*?)$%si", $recent, $matches);
			$category = $matches[1];
			
			$link_args = array(
				'orderby'			=>	(empty($orderby) || $orderby != "date") ? 'name' : 'updated',
				'order'				=>	$order,
				'limit'				=>	$numberitems,				
				'category'			=>	(empty($category) || $category == "all") ? false : $category,
				'exclude'			=>	(empty($exclude)) ? false : $exclude
			);
			
			if ($links = get_bookmarks($link_args)) {
				$items = array();
				$l = 0;
				
				foreach ($links as $link) {				
					$items[$l] = array(
						'title'			=>	$link -> link_name,
						'href'			=>	$link -> link_url,
						'description'	=>	strip_tags($link -> link_description),
						'date'			=>	$link -> link_updated,
					);
					
					$items[$l]['date'] = (empty($link -> link_updated) || strtotime($link -> link_updated) == false || $link -> link_updated == "0000-00-00 00:00:00") ? date("Y-m-d H:i:s", time()) : $link -> link_updated;
					$items[$l]['date'] = date($dateformat, strtotime($items[$l]['date']));
					
					$l++;
				}
			}
		} elseif (ereg("comments", $recent)) {
			if (!empty($exclude)) {
				$exludeids = explode(",", $exclude);
				
				if (!empty($excludeids) && is_array($excludeids)) {
					$where_exclude = " AND";
					$c = 1;
				
					foreach ($excludeids as $cid) {
						if (!empty($cid)) {
							$where_exclude .= "`comment_ID` != '" . $cid . "'";
							
							if ($c < count($excludeids)) {
								$where_exclude .= " AND";
							}
						}
						
						$c++;
					}
				}
			}
		
			global $wpdb;		
			$comments_query = "SELECT * FROM `" . $wpdb -> prefix . "comments` WHERE `comment_approved` = '1' " . $where_exclude . " ORDER BY `comment_date` " . $order . ";";
		
			if ($comments = $wpdb -> get_results($comments_query)) {
				$items = array();
				
				foreach ($comments as $comment) {
					$items[] = array(
						'title'			=>	$comment -> comment_content,
						'href'			=>	get_permalink($comment -> comment_post_ID) . "#comment-" . $comment -> comment_ID,
						'date'			=>	date($dateformat, strtotime($comment -> comment_date)),
					);
				}
			}
		}

		if (!empty($items)) {
			if (!empty($catrsslinks) && $catrsslinks == "Y") {
				$catrss = '';
				
				if (!empty($category) && $allcategories == false) {
					$catrss = '&amp;cat=' . $parent_id;
				}
			
				$oldtitle = $title;
				$rssatag = $title = '<a class="rsswidget" href="' . get_option('home') . '/?feed=rss2' . $catrss . '" title="' . __('RSS Feed', $this -> plugin_name) . '">';
				$title .= '<img style="border:none !important;" src="' . get_option('siteurl') . '/wp-includes/images/rss.png" alt="' . __('rss', $this -> plugin_name) . '" />';
				$title .= '</a> ';
				$title .= $rssatag;
				$title .= $oldtitle;
				$title .= '</a>';
				
				if (!empty($items)) {
					foreach ($items as $ikey => $ival) {
						$items[$ikey]['rsslink'] = true;
					}
				}
			}
		
			$args['title'] = (empty($title)) ? '' : $title;
			$args['titlelink'] = (empty($titlelink)) ? 'N' : $titlelink;
			$args['titlelinkurl'] = (empty($titlelinkurl)) ? '' : $titlelinkurl;
			$args['linkdescriptions'] = (empty($linkdescriptions)) ? '' : $linkdescriptions;
			$args['itemdates'] = (empty($itemdates)) ? '' : $itemdates;
			$args['max_length'] = (empty($max_length)) ? '' : $max_length;
			$this -> render('widget', array('args' => $args, 'items' => $items, 'children' => $children));
		}
	}
	
	function mref_widget_control($widget_args = array()) {
		global $wp_registered_widgets;
		static $updated = false;
		
		if (is_numeric($widget_args)) {
			$widget_args = array('number' => $widget_args);
		}
			
		$widget_args = wp_parse_args($widget_args, array('number' => -1));
		
		if (!empty($widget_args['number']) && is_array($widget_args['number'])) {
			extract($widget_args['number'], EXTR_SKIP);
		} else {
			extract($widget_args, EXTR_SKIP);
		}
		
		$options = get_option('mref-widget');
		if (empty($options) || !is_array($options)) {
			$options = array();
		}
	
		if (!$updated && !empty($_POST['sidebar'])) {
			$sidebar = $_POST['sidebar'];
			$sidebars_widgets = wp_get_sidebars_widgets();
			
			if (!empty($sidebars_widgets[$sidebar])) {
				$this_sidebar = $sidebars_widgets[$sidebar];
			} else {
				$this_sidebar = array();
			}

			if (!empty($this_sidebar)) {			
				foreach ($this_sidebar as $_widget_id ) {
					if ('mref_widget' == $wp_registered_widgets[$_widget_id]['callback'] && isset($wp_registered_widgets[$_widget_id]['params'][0]['number'])) {
						$widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
						
						if (!in_array("mref-" . $widget_number, $_POST['widget-id'])) {
							unset($options[$widget_number]);
						}
					}
				}
			}

			if (!empty($_POST['mref-widget'])) {					
				foreach ($_POST['mref-widget'] as $widget_number => $widget_values) {
					if (!isset($widget_values['title']) && isset($options[$widget_number])) {
						continue;
					}
					
					$title = strip_tags(stripslashes($widget_values['title']));
					$recent = $widget_values['recent'];
					$orderby = $widget_values['orderby'];
					$order = $widget_values['order'];
					$max_length = $widget_values['max_length'];
					$exclude = $widget_values['exclude'];
					$numberitems = $widget_values['numberitems'];
					$rotateposts = $widget_values['rotateposts'];
					$linkdescriptions = $widget_values['linkdescriptions'];
					$catrsslinks = $widget_values['catrsslinks'];
					$itemdates = $widget_values['itemdates'];
					$dateformat = $widget_values['dateformat'];
					$pagesparent = $widget_values['pagesparent'];
					$hide_empty = $widget_values['hide_empty'];
					$titlelink = $widget_values['titlelink'];
					$titlelinkurl = $widget_values['titlelinkurl'];
					$levels = $widget_values['levels'];
					
					$options[$widget_number] = compact('title', 'recent', 'orderby', 'order', 'max_length', 'exclude', 'numberitems', 'rotateposts', 'linkdescriptions', 'catrsslinks', 'itemdates', 'dateformat', 'pagesparent', 'hide_empty', 'titlelink', 'titlelinkurl', 'levels');
				}
			}
	
			update_option('mref-widget', $options);
			$updated = true;
		}
		
		if (-1 == $number) {
			$number = '%i%';
			$title = '';
			$recent = 'posts-all';
			$orderby = 'date';
			$order = 'DESC';
			$max_length = 50;
			$exclude = '';
			$numberitems = 5;
			$rotateposts = false;
			$itemdates = false;
			$dateformat = "Y-m-d H:i:s";
			$pagesparent = "Y";
			$hide_empty = "Y";
			$titlelink = "";
			$titlelinkurl = "";
			$levels = 1;
		} else {
			$title = $options[$number]['title'];
			$recent = $options[$number]['recent'];
			$orderby = $options[$number]['orderby'];
			$order = $options[$number]['order'];
			$max_length = $options[$number]['max_length'];
			$exclude = $options[$number]['exclude'];
			$numberitems = $options[$number]['numberitems'];
			$rotateposts = $options[$number]['rotateposts'];
			$linkdescriptions = $options[$number]['linkdescriptions'];
			$catrsslinks = $options[$number]['catrsslinks'];
			$itemdates = $options[$number]['itemdates'];
			$dateformat = $options[$number]['dateformat'];
			$pagesparent = $options[$number]['pagesparent'];
			$hide_empty = $options[$number]['hide_empty'];
			$titlelink = $options[$number]['titlelink'];
			$titlelinkurl = $options[$number]['titlelinkurl'];
			$levels = $options[$number]['levels'];
		}
		
		?>

		<?php if (!empty($this -> adversion) && $this -> adversion == true) : ?>		
			<div class="<?= $this -> pre; ?>notice">
				<p><?php _e('MaxRef free/link supported.', $this -> plugin_name); ?> <a href="http://www.webfadds.com/plugins/maxrefnonad" target="_blank" title="<?php _e('Donate to the MaxRef Widgets plugin', $this -> plugin_name); ?>"><?php _e('Make donation', $this -> plugin_name); ?></a> <?php _e(' to download MaxRef with no link.', $this -> plugin_name); ?></p>
			</div>
		<?php endif; ?>
		
		<p>
			<label for="mref_widget_<?= $number; ?>_title">
				<?php _e('Title', $this -> plugin_name); ?> :
				<input id="mref_widget_<?= $number; ?>_title" type="text" class="widefat" name="mref-widget[<?= $number; ?>][title]" value="<?= $title; ?>" />
			</label>
		</p>
		<p>
			<label for="mref_widget_<?= $number; ?>_recent">
				<?php _e('Display', $this -> plugin_name); ?> :
				<select onchange="change_display(this.value,'<?= $number; ?>');" class="widefat" id="mref_widget_<?= $number; ?>_recent" name="mref-widget[<?= $number; ?>][recent]">
					<option value="">- <?php _e('Select', $this -> plugin_name); ?> -</option>
					<optgroup label="<?php _e('Posts', $this -> plugin_name); ?>">
						<option <?= (!empty($recent) && $recent == "posts-all") ? 'selected="selected"' : ''; ?> value="posts-all"><?php _e('Posts', $this -> plugin_name); ?> :: <?php _e('All Categories', $this -> plugin_name); ?></option>
						<?php $categories = get_categories(array('number' => false, 'order' => "ASC", 'orderby' => "name")); ?>
						<?php if (!empty($categories)) : ?>
							<?php foreach ($categories as $category) : ?>
								<option <?= (!empty($recent) && $recent == "posts-" . $category -> cat_ID) ? 'selected="selected"' : ''; ?> value="posts-<?= $category -> cat_ID; ?>"><?php _e('Posts', $this -> plugin_name); ?> :: <?= $category -> cat_name; ?></option>
							<?php endforeach; ?>
						<?php endif; ?>
					</optgroup>
					<optgroup label="<?php _e('Pages', $this -> plugin_name); ?>">
						<option <?= (!empty($recent) && $recent == "pages-all") ? 'selected="selected"' : ''; ?> value="pages-all"><?php _e('All Pages', $this -> plugin_name); ?></option>
						<?php if ($pages = get_pages(array('child_of' => 0))) : ?>
							<?php foreach ($pages as $page) : ?>
								<option <?= (!empty($recent) && $recent == "pages-" . $page -> ID) ? 'selected="selected"' : ''; ?> value="pages-<?= $page -> ID; ?>"><?php _e('Children of', $this -> plugin_name); ?> : <?= $page -> post_title; ?> (<?= count(get_pages(array('child_of' => $page -> ID))); ?>)</option>
							<?php endforeach; ?>
						<?php endif; ?>
					</optgroup>
					<optgroup label="<?php _e('Categories', $this -> plugin_name); ?>">
						<option <?= (!empty($recent) && $recent == "categories-all") ? 'selected="selected"' : ''; ?> value="categories-all"><?php _e('All Categories', $this -> plugin_name); ?></option>
						<?php $categories = get_categories(array('number' => false, 'hide_empty' => false, 'child_of' => 0, 'order' => "ASC", 'orderby' => "name")); ?>
						<?php if (!empty($categories)) : ?>
							<?php foreach ($categories as $category) : ?>
								<option <?= (!empty($recent) && $recent == "categories-" . $category -> cat_ID) ? 'selected="selected"' : ''; ?> value="categories-<?= $category -> cat_ID; ?>"><?php _e('Children of', $this -> plugin_name); ?> : <?= $category -> cat_name; ?> (<?= count(get_categories(array('child_of' => $category -> cat_ID, 'number' => false, 'hide_empty' => false))); ?>)</option>
							<?php endforeach; ?>
						<?php endif; ?>
					</optgroup>
					<optgroup label="<?php _e('Links', $this -> plugin_name); ?>">
						<option <?= (!empty($recent) && $recent == "links-all") ? 'selected="selected"' : ''; ?> value="links-all"><?php _e('Links', $this -> plugin_name); ?> :: <?php _e('All Categories', $this -> plugin_name); ?></option>
						<?php $categories = get_categories(array('type' => 'link', 'number' => false, 'order' => "ASC", 'orderby' => "name")); ?>
						<?php if (!empty($categories)) : ?>
							<?php foreach ($categories as $category) : ?>
								<option <?= (!empty($recent) && $recent == "links-" . $category -> cat_ID) ? 'selected="selected"' : ''; ?> value="links-<?= $category -> cat_ID; ?>"><?php _e('Links', $this -> plugin_name); ?> :: <?= $category -> cat_name; ?></option>
							<?php endforeach; ?>
						<?php endif; ?>
					</optgroup>
					<optgroup label="<?php _e('Comments', $this -> plugin_name); ?>">
						<option <?= (!empty($recent) && $recent == "comments") ? 'selected="selected"' : ''; ?> value="comments"><?php _e('All Comments', $this -> plugin_name); ?></option>
					</optgroup>
				</select>
			</label>
		</p>
		
		<div id="levels_div<?= $number; ?>" style="display:<?= (!empty($recent) && (ereg("categories", $recent) || ereg("pages", $recent))) ? 'block' : 'none'; ?>;">
			<p>
				<?php _e('Children Levels', $this -> plugin_name); ?> 
				<input type="text" name="mref-widget[<?= $number; ?>][levels]" value="<?= $levels; ?>" style="width:25px;" />
			</p>
		</div>
		
		<div id="pages_div<?= $number; ?>" style="display:<?= (!empty($recent) && (ereg("pages", $recent) || ereg("categories", $recent))) ? 'block' : 'none'; ?>;">
			<p>
				<?php _e('Show Parent', $this -> plugin_name); ?> :
				<label><input <?= (!empty($pagesparent) && $pagesparent == "Y") ? 'checked="checked"' : ''; ?> type="radio" name="mref-widget[<?= $number; ?>][pagesparent]" value="Y" /> <?php _e('Yes', $this -> plugin_name); ?></label>
				<label><input <?= (!empty($pagesparent) && $pagesparent == "N") ? 'checked="checked"' : ''; ?> type="radio" name="mref-widget[<?= $number; ?>][pagesparent]" value="N" /> <?php _e('No', $this -> plugin_name); ?></label>
			</p>
		</div>
		
		<div id="hideempty_div<?= $number; ?>" style="display:<?= (!empty($recent) && ereg("categories", $recent)) ? 'block' : 'none'; ?>;">
			<p>
				<?php _e('Hide Empty Categories', $this -> plugin_name); ?> :
				<label><input <?= (empty($hide_empty) || $hide_empty == "Y") ? 'checked="checked"' : ''; ?> type="radio" name="mref-widget[<?= $number; ?>][hide_empty]" value="Y" id="mref_widget_<?= $number; ?>_hideemptyY" /> <?php _e('Yes', $this -> plugin_name); ?></label>
				<label><input <?= ($hide_empty == "N") ? 'checked="checked"' : ''; ?> type="radio" name="mref-widget[<?= $number; ?>][hide_empty]" value="N" id="mref_widget_<?= $number; ?>_hideemptyN" /> <?php _e('No', $this -> plugin_name); ?></label>
			</p>
		</div>
		
		<p>
			<label for="mref_widget_<?= $number; ?>_numberitems">
				<?php _e('Number of Items', $this -> plugin_name); ?> :
				<input style="width:45px; text-align:center;" id="mref_widget_<?= $number; ?>_numberitems" type="text" name="mref-widget[<?= $number; ?>][numberitems]" size="3" value="<?= $numberitems; ?>" /> <?php _e('items', $this -> plugin_name); ?>
				<br/><small><?php _e('leave empty to show all', $this -> plugin_name); ?></small></td>
			</label>
		</p>
		<p>
			<label for="mref_widget_<?= $number; ?>_rotateposts" id="mref_widget_<?= $number; ?>_rotatepostslabel" style="color:#999999;">
				<input type="checkbox" <?= (!empty($rotateposts) && $rotateposts == "Y") ? 'checked="checked"' : 'disabled="disabled"'; ?> name="mref-widget[<?= $number; ?>][rotateposts]" value="Y" id="mref_widget_<?= $number; ?>_rotateposts" />
				<?php _e('Rotate Number of Posts Specified', $this -> plugin_name); ?>
				<br/><small><?php _e('only works when "Display" is set to Posts:: All Categories', $this -> plugin_name); ?></small>
			</label>
		</p>
		<p>
			<label for="mref_widget_<?= $number; ?>_orderby_name">
				<?php _e('Sort By', $this -> plugin_name); ?> :
				<label><input id="mref_widget_<?= $number; ?>_orderby_name" <?= ((empty($orderby)) || (!empty($orderby) && $orderby == "name")) ? 'checked="checked"' : ''; ?> type="radio" name="mref-widget[<?= $number; ?>][orderby]" value="name" /> <?php _e('Name', $this -> plugin_name); ?></label>
				<label><input id="mref_widget_<?= $number; ?>_orderby_date" <?= (!empty($orderby) && $orderby == "date") ? 'checked="checked"' : ''; ?> type="radio" name="mref-widget[<?= $number; ?>][orderby]" value="date" /> <?php _e('Date', $this -> plugin_name); ?></label>
			</label>
		</p>
		<p>
			<label for="mref_widget_<?= $number; ?>_order_asc">
				<?php _e('Sort Direction', $this -> plugin_name); ?> :
				<label><input id="mref_widget_<?= $number; ?>_order_asc" <?= ((empty($order)) || (!empty($order) && $order == "ASC")) ? 'checked="checked"' : ''; ?> type="radio" name="mref-widget[<?= $number; ?>][order]" value="ASC" /> <?php _e('Ascending', $this -> plugin_name); ?></label>
				<label><input id="mref_widget_<?= $number; ?>_order_desc" <?= (!empty($order) && $order == "DESC") ? 'checked="checked"' : ''; ?> type="radio" name="mref-widget[<?= $number; ?>][order]" value="DESC" /> <?php _e('Descending', $this -> plugin_name); ?></label>
			</label>
		</p>
		<p>
			<?php _e('Options', $this -> plugin_name); ?> :<br/>
			<label><input <?= (!empty($linkdescriptions) && $linkdescriptions == "Y") ? 'checked="checked"' : ''; ?> type="checkbox" name="mref-widget[<?= $number; ?>][linkdescriptions]" value="Y" /> <?php _e('Show link descriptions', $this -> plugin_name); ?></label><br/>
			<label><input <?= (!empty($catrsslinks) && $catrsslinks == "Y") ? 'checked="checked"' : ''; ?> onclick="titlelinktoggle('<?= $number; ?>');" type="checkbox" id="mref_widget_<?= $number; ?>_catrsslinks" name="mref-widget[<?= $number; ?>][catrsslinks]" value="Y" /> <?php _e('Show RSS links for categories', $this -> plugin_name); ?></label><br/>			
			<label><input onclick="showitemdates(<?= $number; ?>);" <?= (!empty($itemdates) && $itemdates == "Y") ? 'checked="checked"' : ''; ?> type="checkbox" name="mref-widget[<?= $number; ?>][itemdates]" value="Y" id="mref_widget_<?= $number; ?>_itemdates" /> <?php _e('Show date for each item', $this -> plugin_name); ?></label><br/>			
			<label><input onclick="titlelinktoggle('<?= $number; ?>');" <?= (!empty($titlelink) && $titlelink == "Y") ? 'checked="checked"' : ''; ?> id="mref_widget_<?= $number; ?>_titlelink" type="checkbox" name="mref-widget[<?= $number; ?>][titlelink]" value="Y" /> <?php _e('Apply link to title', $this -> plugin_name); ?></label>
			
			<div id="mref_widget_<?= $number; ?>_titlelinkdiv" style="display:<?= (!empty($titlelink) && $titlelink == "Y") ? 'block' : 'none'; ?>;">
				<label for="mref_widget_<?= $number; ?>_titlelinkurl">
					<?php _e('Link URL', $this -> plugin_name); ?> :
					<input type="text" class="widefat" name="mref-widget[<?= $number; ?>][titlelinkurl]" value="<?= $titlelinkurl; ?>" id="mref_widget_<?= $number; ?>_titlelinkurl" />
				</label>
			</div>
		</p>
			<p id="mref_widget_<?= $number; ?>_itemdatesY" style="display:<?= (!empty($itemdates) && $itemdates == "Y") ? 'block' : 'none'; ?>;">
				<label for="mref_widget_<?= $number; ?>_dateformat">
					<?php $dateformats = $this -> get_option('dateformats'); ?>
					<?php _e('Date Format', $this -> plugin_name); ?> :
					<select id="mref_widget_<?= $number; ?>_dateformat" name="mref-widget[<?= $number; ?>][dateformat]" class="widefat">
						<option value="">- <?php _e('Select Date Format', $this -> plugin_name); ?> -</option>
						<?php foreach ($dateformats as $format) : ?>
							<option value="<?= $format; ?>" <?= (!empty($dateformat) && $dateformat == $format) ? 'selected="selected"' : ''; ?>><?= date($format, time()); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</p>
		<p>
			<label for="mref_widget_<?= $number; ?>_max_length">
				<?php _e('Max Length', $this -> plugin_name); ?> :
				<input style="width:45px; text-align:center;" type="text" id="mref_widget_<?= $number; ?>_max_length" size="5" name="mref-widget[<?= $number; ?>][max_length]" value="<?= $max_length; ?>" /> <?php _e('characters', $this -> plugin_name); ?>
				<br/><small>leave empty to show full titles</small>
			</label>
		</p>
		<p>
			<label for="mref_widget_<?= $number; ?>_exclude">
				<?php _e('Exclude', $this -> plugin_name); ?> :
				<input class="widefat" id="mref_widget_<?= $number; ?>_exclude" type="text" name="mref-widget[<?= $number; ?>][exclude]" value="<?= $exclude; ?>" /> <br/><small>Enter comma separated IDs</small>
			</label>
		</p>
		
		<?php
		
		if (!empty($recent)) {
			?>
			
			<script type="text/javascript">
			change_display('<?= $recent; ?>','<?= $number; ?>');
			</script>
			
			<?php
		}
	}
	
	function mref_widget_register() {		
		if (function_exists('register_sidebar_widget')) {
			if (!$options = get_option('mref-widget')) {
				$options = array();
			}
		
			$widget_options = array('classname' => 'mref-widget', 'description' => __('Output comments, links, categories, posts and more in your sidebar(s)', $this -> plugin_name));	
			$control_options = array('id_base' => 'mref', 'width' => 350, 'height' => 300);	
			$name = __('MaxRef Widget', $this -> plugin_name);
			
			if (!empty($options)) {
				foreach ($options as $okey => $oval) {
					$id = 'mref-' . $okey;
										
					wp_register_sidebar_widget($id, $name, array($this, 'mref_widget'), $widget_options, array('number' => $okey));
					wp_register_widget_control($id, $name, array($this, 'mref_widget_control'), $control_options, array('number' => $okey));
				}
			} else {
				$id = 'mref-1';
				wp_register_sidebar_widget($id, $name, array($this, 'mref_widget'), $widget_options, array('number' => -1));
				wp_register_widget_control($id, $name, array($this, 'mref_widget_control'), $control_options, array('number' => -1));
			}
		}
	}
	
	function admin_head() {
		$this -> render('head', false, true, 'admin');
	}
	
	function wp_head() {
		$this -> render('head', false, true, 'default');
	}
}

$mrefWidgets = new mrefWidgets();

?>