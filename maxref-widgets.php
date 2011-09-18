<?php
/*
Plugin Name: MaxRef Widgets
Plugin URI: http://WebFadds.com/wordpress-services/plugins/
Description: MaxRef Widgets enables you to display multiple sidebar widgets to maximize how your visitors reference to your pages, your posts, links, categories, RSS feeds, and comments.
Version: 2.0
Author: Scott Frangos
Author URI: http://webfadds.com
License: GPLv2
*/

/*  Copyright 2011  Scott Frangos, WebFadds.com  (email : sales@webfadds.com)
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
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
		$title = $options[$number]['title'];
		$candisplay='';

		if($options[$number]['allcategories'] == '' && $options[$number]['postcategory']=='') {
			$candisplay='yes';
		}else{
			if (is_category()){
				if($options[$number]['allcategories'] == 'Y') {
					//dispaly the widget here
					$candisplay='yes';
				}else{
					if($options[$number]['postcategory']!='') {
						$curcatid = get_query_var('cat');
						$postcategory	=	explode(",", $options[$number]['postcategory']);
							foreach($postcategory as $postcat){
									if($curcatid == $postcat) {
										$candisplay='yes';
									}//endif

							}//endforeach
					}//endif
				}//end else
			}//endif
		}//endif

		if($candisplay){
			//dispaly the widget here
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
							'post_ID'		=>  $post -> ID,
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
					'post_parent'			=>	(empty($child_of) || $child_of == "all") ? 0 : $child_of,
					'order'					=>	(empty($order) || $order == "ASC") ? 'ASC' : 'DESC',
					'orderby'				=>	(empty($orderby) || $orderby == "name") ? 'post_title' : 'post_date',
					'post__not_in'			=>	(empty($exclude)) ? false : explode(",",$exclude),
					'post_type'				=>	'page',
					'posts_per_page'		=>  (empty($numberitems)) ? false : (int)$numberitems
				);

				 $pages =query_posts($page_args);
					foreach ($pages as $page) {
						$items[] = array(
							'title'			=>	$page -> post_title,
							'href'			=>	get_permalink($page -> ID),
							'date'			=>	date($dateformat, strtotime($page -> post_date)),
							'page_ID'		=>  $page -> ID							
						);

						$childargs = array(
							'child_of'			=>	$page -> ID,
							'order'				=>	$order,
							'orderby'			=>	$orderby,
							'exclude'			=>	$exclude
						);
						$usedpages[] = $page -> ID;					
						$this -> get_pages($childargs);
					}
				wp_reset_query();
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
				'hide_empty'		=>	(empty($hide_empty) || $hide_empty == "Y") ? true : false
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
						'cat_ID'		=>	$category -> cat_ID
					);

					$childargs = array(
						'parent'		=>	$category -> cat_ID,
						'order'			=>	$order,
						'orderby'		=>	(empty($orderby) || $orderby != "date") ? 'name' : 'ID',
						'exclude'		=>	(empty($exclude)) ? false : $exclude,
						'hide_empty'	=>	true
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
						'date'			=>	$link -> link_updated
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
			$comments_query = "SELECT * FROM `" . $wpdb -> prefix . "comments` WHERE `comment_approved` = '1' " . $where_exclude . " ORDER BY `comment_date` " . $order . " LIMIT $numberitems ;";
			if ($comments = $wpdb -> get_results($comments_query)) {
				$items = array();
				foreach ($comments as $comment) {
					$items[] = array(
						'title'			=>	$comment -> comment_content,
						'comment_ID'    => $comment -> comment_ID,
						'href'			=>	get_permalink($comment -> comment_post_ID) . "#comment-" . $comment -> comment_ID,
						'date'			=>	date($dateformat, strtotime($comment -> comment_date)),
					);
				}
			}
			}

			if (!empty($items)) {
			if (!empty($catrsslinks) && $catrsslinks == "Y") {
				$catrss = '';
				if($recent == "categories-all")
				  $catrss = 'rss2&amp;cat=all';
				else if($post_type == "post" )
				{
				  $catrss = 'rss2';
				}
				else if($recent=="comments")
				{
				  $catrss = "comments-rss2";
				}
				else
				$catrss = 'rss2';	
				$oldtitle = $title;				
				if($post_type != "page"){
				$rssatag = $title = '<a class="rsswidget" href="' . get_option('home') . '/?feed=' . $catrss . '" title="' . __('RSS Feed', $this -> plugin_name) . '">';
				$title .= '<img style="border:none !important;" src="' . get_option('siteurl') . '/wp-includes/images/rss.png" alt="' . __('rss', $this -> plugin_name) . '" />';
				$title .= '</a> ';				
				$title .= $rssatag;
				$title .= $oldtitle;
				$title .= '</a>';
				}
				if (!empty($items)) {
					foreach ($items as $ikey => $ival) {
						if($recent == "comments")
							$items[$ikey]['rsslink'] = false;
						else
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

					$allcategories	=	$widget_values['allcategories'];
					if($widget_values['postcategory']!='') {
						$postcategory	=	implode(",", $widget_values['postcategory']);
					}

					$title				=	strip_tags(stripslashes($widget_values['title']));
					$recent				=	$widget_values['recent'];
					$orderby			=	$widget_values['orderby'];
					$order				=	$widget_values['order'];
					$max_length 		=	$widget_values['max_length'];
					$exclude			=	$widget_values['exclude'];
					$numberitems		=	$widget_values['numberitems'];
					$rotateposts 		=	$widget_values['rotateposts'];
					$linkdescriptions 	=	$widget_values['linkdescriptions'];
					$catrsslinks 		=	$widget_values['catrsslinks'];
					$itemdates 			=	$widget_values['itemdates'];
					$dateformat 		=	$widget_values['dateformat'];
					$pagesparent 		=	$widget_values['pagesparent'];
					$hide_empty 		=	$widget_values['hide_empty'];
					$titlelink 			=	$widget_values['titlelink'];
					$titlelinkurl 		=	$widget_values['titlelinkurl'];
					$levels 			=	$widget_values['levels'];
					$options[$widget_number] = compact('title', 'recent', 'orderby', 'order', 'max_length', 'exclude', 'numberitems', 'rotateposts', 'linkdescriptions', 'catrsslinks', 'itemdates', 'dateformat', 'pagesparent', 'hide_empty', 'titlelink', 'titlelinkurl', 'levels', 'postcategory', 'allcategories');
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
			$allcategories	=	$options[$number]['allcategories'];
			$postcategory = explode(',',$options[$number]['postcategory']);
		}

		?>
<?php if (!empty($this -> adversion) && $this -> adversion == true) : ?>
<div class="<?php echo $this -> pre; ?>notice">
  <p>
    <?php _e('', $this -> plugin_name); ?>
    <a href="http://www.webfadds.com/plugins/maxrefnonad" target="_blank" title="<?php _e('Donate to the MaxRef Widgets plugin', $this -> plugin_name); ?>">
    <?php _e('', $this -> plugin_name); ?>
    </a>
    <?php _e(' ', $this -> plugin_name); ?>
  </p>
</div>
<?php endif; ?>
<?php if ($widget_values['call']=='') {
		$widget_values['call'] = "display";
	}

?>
<script type="text/javascript">
function loaddisplaytab()	{
	jQuery(document).ready(function($) {
		$(".display").addClass("active");
		$(".location").removeClass("active");
		$('.displaypanel').show();
		$('.locationpanel').hide();
	});
}
function loadlocationtab()	{
	jQuery(document).ready(function($) {
		$(".location").addClass("active");
		$(".display").removeClass("active");
		$('.locationpanel').show();
		$('.displaypanel').hide();
	});
}
function displaytabmover()	{
	$(".display").addClass("hactive");
}
function displaytabmout()	{
	$(".display").removeClass("hactive");
}
function locationtabmover()	{
	$(".location").addClass("hactive");
}
function locationtabmout()	{
	$(".location").removeClass("hactive");
}
</script>
<div id="toppanel">
  <input type="text" onclick="loaddisplaytab();" onmouseover="displaytabmover();" onmouseout="displaytabmout();"  name="type" id="displaybutton" value="" <?php if ($widget_values['call'] == "display") echo "checked='checked'"; ?> class="display active" />
  <input type="text" onclick="loadlocationtab();" onmouseover="locationtabmover();" onmouseout="locationtabmout();" name="type" id="locationbutton" value="" <?php if ($widget_values['call'] == "location") echo "checked='checked'"; ?> class="location" />
</div>
<div class="displaypanel" style="display:<?php if ($widget_values['call'] == "display") { echo "block"; } else { echo "none"; } ?>;"> <br />
  <br />
  <p>
    <label for="mref_widget_<?php echo $number; ?>_title">
    <?php _e('Title', $this -> plugin_name); ?>
    :
    <input id="mref_widget_<?php echo $number; ?>_title" type="text" class="widefat" name="mref-widget[<?php echo $number; ?>][title]" value="<?php echo $title; ?>" />
    </label>
  </p>
  <p>
    <label for="mref_widget_<?php echo $number; ?>_recent">
    <?php _e('Display', $this -> plugin_name); ?>
    :
    <select onchange="change_display(this.value,'<?php echo $number; ?>');" class="widefat" id="mref_widget_<?php echo $number; ?>_recent" name="mref-widget[<?php echo $number; ?>][recent]">
      <option value="">-
      <?php _e('Select', $this -> plugin_name); ?>
      -</option>
      <optgroup label="<?php _e('Posts', $this -> plugin_name); ?>">
      <option <?php echo (!empty($recent) && $recent == "posts-all") ? 'selected="selected"' : ''; ?> value="posts-all">
      <?php _e('Posts', $this -> plugin_name); ?>
      ::
      <?php _e('All Categories', $this -> plugin_name); ?>
      </option>
      <?php $categories = get_categories(array('number' => false, 'order' => "ASC", 'orderby' => "name")); ?>
      <?php if (!empty($categories)) : ?>
      <?php foreach ($categories as $category) : ?>
      <option <?php echo (!empty($recent) && $recent == "posts-" . $category -> cat_ID) ? 'selected="selected"' : ''; ?> value="posts-<?php echo $category -> cat_ID; ?>">
      <?php _e('Posts', $this -> plugin_name); ?>
      ::
      <?php echo $category -> cat_name; ?>
      </option>
      <?php endforeach; ?>
      <?php endif; ?>
      </optgroup>
      <optgroup label="<?php _e('Pages', $this -> plugin_name); ?>">
      <option <?php echo (!empty($recent) && $recent == "pages-all") ? 'selected="selected"' : ''; ?> value="pages-all">
      <?php _e('All Pages', $this -> plugin_name); ?>
      </option>
      <?php if ($pages = get_pages(array('child_of' => 0))) : ?>
      <?php foreach ($pages as $page) : ?>
      <option <?php echo (!empty($recent) && $recent == "pages-" . $page -> ID) ? 'selected="selected"' : ''; ?> value="pages-<?php echo $page -> ID; ?>">
      <?php _e('Children of', $this -> plugin_name); ?>
      :
      <?php echo $page -> post_title; ?>
      (
      <?php echo count(get_pages(array('child_of' => $page -> ID))); ?>
      )</option>
      <?php endforeach; ?>
      <?php endif; ?>
      </optgroup>
      <optgroup label="<?php _e('Categories', $this -> plugin_name); ?>">
      <option <?php echo (!empty($recent) && $recent == "categories-all") ? 'selected="selected"' : ''; ?> value="categories-all">
      <?php _e('All Categories', $this -> plugin_name); ?>
      </option>
      <?php $categories = get_categories(array('number' => false, 'hide_empty' => false, 'child_of' => 0, 'order' => "ASC", 'orderby' => "name")); ?>
      <?php if (!empty($categories)) : ?>
      <?php foreach ($categories as $category) : ?>
      <option <?php echo (!empty($recent) && $recent == "categories-" . $category -> cat_ID) ? 'selected="selected"' : ''; ?> value="categories-<?php echo $category -> cat_ID; ?>">
      <?php _e('Children of', $this -> plugin_name); ?>
      :
      <?php echo $category -> cat_name; ?>
      (
      <?php echo count(get_categories(array('child_of' => $category -> cat_ID, 'number' => false, 'hide_empty' => false))); ?>
      )</option>
      <?php endforeach; ?>
      <?php endif; ?>
      </optgroup>
      <optgroup label="<?php _e('Links', $this -> plugin_name); ?>">
      <option <?php echo (!empty($recent) && $recent == "links-all") ? 'selected="selected"' : ''; ?> value="links-all">
      <?php _e('Links', $this -> plugin_name); ?>
      ::
      <?php _e('All Categories', $this -> plugin_name); ?>
      </option>
      <?php $categories = get_categories(array('type' => 'link', 'number' => false, 'order' => "ASC", 'orderby' => "name")); ?>
      <?php if (!empty($categories)) : ?>
      <?php foreach ($categories as $category) : ?>
      <option <?php echo (!empty($recent) && $recent == "links-" . $category -> cat_ID) ? 'selected="selected"' : ''; ?> value="links-<?php echo $category -> cat_ID; ?>">
      <?php _e('Links', $this -> plugin_name); ?>
      ::
      <?php echo $category -> cat_name; ?>
      </option>
      <?php endforeach; ?>
      <?php endif; ?>
      </optgroup>
      <optgroup label="<?php _e('Comments', $this -> plugin_name); ?>">
      <option <?php echo (!empty($recent) && $recent == "comments") ? 'selected="selected"' : ''; ?> value="comments">
      <?php _e('All Comments', $this -> plugin_name); ?>
      </option>
      </optgroup>
    </select>
    </label>
  </p>
  <div id="levels_div<?php echo $number; ?>" style="display:<?php echo (!empty($recent) && (ereg("categories", $recent) || ereg("pages", $recent))) ? 'block' : 'none'; ?>;">
    <p>
      <?php _e('Children Levels', $this -> plugin_name); ?>
      <input type="text" name="mref-widget[<?php echo $number; ?>][levels]" value="<?php echo $levels; ?>" style="width:25px;" />
    </p>
  </div>
  <div id="pages_div<?php echo $number; ?>" style="display:<?php echo (!empty($recent) && (ereg("pages", $recent) || ereg("categories", $recent))) ? 'block' : 'none'; ?>;">
    <p>
      <?php _e('Show Parent', $this -> plugin_name); ?>
      :
      <label>
      <input <?php echo (!empty($pagesparent) && $pagesparent == "Y") ? 'checked="checked"' : ''; ?> type="radio" name="mref-widget[<?php echo $number; ?>][pagesparent]" value="Y" />
      <?php _e('Yes', $this -> plugin_name); ?>
      </label>
      <label>
      <input <?php echo (!empty($pagesparent) && $pagesparent == "N") ? 'checked="checked"' : ''; ?> type="radio" name="mref-widget[<?php echo $number; ?>][pagesparent]" value="N" />
      <?php _e('No', $this -> plugin_name); ?>
      </label>
    </p>
  </div>
  <div id="hideempty_div<?php echo $number; ?>" style="display:<?php echo (!empty($recent) && ereg("categories", $recent)) ? 'block' : 'none'; ?>;">
    <p>
      <?php _e('Hide Empty Categories', $this -> plugin_name); ?>
      :
      <label>
      <input <?php echo (empty($hide_empty) || $hide_empty == "Y") ? 'checked="checked"' : ''; ?> type="radio" name="mref-widget[<?php echo $number; ?>][hide_empty]" value="Y" id="mref_widget_<?php echo $number; ?>_hideemptyY" />
      <?php _e('Yes', $this -> plugin_name); ?>
      </label>
      <label>
      <input <?php echo ($hide_empty == "N") ? 'checked="checked"' : ''; ?> type="radio" name="mref-widget[<?php echo $number; ?>][hide_empty]" value="N" id="mref_widget_<?php echo $number; ?>_hideemptyN" />
      <?php _e('No', $this -> plugin_name); ?>
      </label>
    </p>
  </div>
  <p>
    <label for="mref_widget_<?php echo $number; ?>_numberitems">
    <?php _e('Number of Items', $this -> plugin_name); ?>
    :
    <input style="width:45px; text-align:center;" id="mref_widget_<?php echo $number; ?>_numberitems" type="text" name="mref-widget[<?php echo $number; ?>][numberitems]" size="3" value="<?php echo $numberitems; ?>" />
    <?php _e('items', $this -> plugin_name); ?>
    <br/>
    <small>
    <?php _e('leave empty to show all', $this -> plugin_name); ?>
    </small>
    </td>
    </label>
  </p>
  <p>
    <label for="mref_widget_<?php echo $number; ?>_rotateposts" id="mref_widget_<?php echo $number; ?>_rotatepostslabel" style="color:#999999;">
    <input type="checkbox" <?php echo (!empty($rotateposts) && $rotateposts == "Y") ? 'checked="checked"' : 'disabled="disabled"'; ?> name="mref-widget[<?php echo $number; ?>][rotateposts]" value="Y" id="mref_widget_<?php echo $number; ?>_rotateposts" />
    <?php _e('Rotate Number of Posts Specified', $this -> plugin_name); ?>
    <br/>
    <small>
    <?php _e('only works when "Display" is set to Posts:: All Categories', $this -> plugin_name); ?>
    </small> </label>
  </p>
  <p>
    <label for="mref_widget_<?php echo $number; ?>_orderby_name">
    <?php _e('Sort By', $this -> plugin_name); ?>
    :
    <label>
    <input id="mref_widget_<?php echo $number; ?>_orderby_name" <?php echo ((empty($orderby)) || (!empty($orderby) && $orderby == "name")) ? 'checked="checked"' : ''; ?> type="radio" name="mref-widget[<?php echo $number; ?>][orderby]" value="name" />
    <?php _e('Name', $this -> plugin_name); ?>
    </label>
    <label>
    <input id="mref_widget_<?php echo $number; ?>_orderby_date" <?php echo (!empty($orderby) && $orderby == "date") ? 'checked="checked"' : ''; ?> type="radio" name="mref-widget[<?php echo $number; ?>][orderby]" value="date" />
    <?php _e('Date', $this -> plugin_name); ?>
    </label>
    </label>
  </p>
  <p>
    <label for="mref_widget_<?php echo $number; ?>_order_asc">
    <?php _e('Sort Direction', $this -> plugin_name); ?>
    :
    <label>
    <input id="mref_widget_<?php echo $number; ?>_order_asc" <?php echo ((empty($order)) || (!empty($order) && $order == "ASC")) ? 'checked="checked"' : ''; ?> type="radio" name="mref-widget[<?php echo $number; ?>][order]" value="ASC" />
    <?php _e('Ascending', $this -> plugin_name); ?>
    </label>
    <label>
    <input id="mref_widget_<?php echo $number; ?>_order_desc" <?php echo (!empty($order) && $order == "DESC") ? 'checked="checked"' : ''; ?> type="radio" name="mref-widget[<?php echo $number; ?>][order]" value="DESC" />
    <?php _e('Descending', $this -> plugin_name); ?>
    </label>
    </label>
  </p>
  <p>
    <?php _e('Options', $this -> plugin_name); ?>
    :<br/>
    <label>
    <input <?php echo (!empty($linkdescriptions) && $linkdescriptions == "Y") ? 'checked="checked"' : ''; ?> type="checkbox" name="mref-widget[<?php echo $number; ?>][linkdescriptions]" value="Y" />
    <?php _e('Show link descriptions', $this -> plugin_name); ?>
    </label>
    <br/>
    <label>
    <input <?php echo (!empty($catrsslinks) && $catrsslinks == "Y") ? 'checked="checked"' : ''; ?> onclick="titlelinktoggle('<?php echo $number; ?>');" type="checkbox" id="mref_widget_<?php echo $number; ?>_catrsslinks" name="mref-widget[<?php echo $number; ?>][catrsslinks]" value="Y" />
    <?php _e('Show RSS links for categories', $this -> plugin_name); ?>
    </label>
    <br/>
    <label>
    <input onclick="showitemdates('<?php echo $number; ?>');" <?php echo (!empty($itemdates) && $itemdates == "Y") ? 'checked="checked"' : ''; ?> type="checkbox" name="mref-widget[<?php echo $number; ?>][itemdates]" value="Y" id="mref_widget_<?php echo $number; ?>_itemdates" />
    <?php _e('Show date for each item', $this -> plugin_name); ?>
    </label>
    <br/>
    <label>
    <input onclick="titlelinktoggle('<?php echo $number; ?>');" <?php echo (!empty($titlelink) && $titlelink == "Y") ? 'checked="checked"' : ''; ?> id="mref_widget_<?php echo $number; ?>_titlelink" type="checkbox" name="mref-widget[<?php echo $number; ?>][titlelink]" value="Y" />
    <?php _e('Apply link to title', $this -> plugin_name); ?>
    </label>
  <div id="mref_widget_<?php echo $number; ?>_titlelinkdiv" style="display:<?php echo (!empty($titlelink) && $titlelink == "Y") ? 'block' : 'none'; ?>;">
    <label for="mref_widget_<?php echo $number; ?>_titlelinkurl">
    <?php _e('Link URL', $this -> plugin_name); ?>
    :
    <input type="text" class="widefat" name="mref-widget[<?php echo $number; ?>][titlelinkurl]" value="<?php echo $titlelinkurl; ?>" id="mref_widget_<?php echo $number; ?>_titlelinkurl" />
    </label>
  </div>
  </p>
  <p id="mref_widget_<?php echo $number; ?>_itemdatesY" style="display:<?php echo (!empty($itemdates) && $itemdates == "Y") ? 'block' : 'none'; ?>;">
    <label for="mref_widget_<?php echo $number; ?>_dateformat">
    <?php $dateformats = $this -> get_option('dateformats'); ?>
    <?php _e('Date Format', $this -> plugin_name); ?>
    :
    <select id="mref_widget_<?php echo $number; ?>_dateformat" name="mref-widget[<?php echo $number; ?>][dateformat]" class="widefat">
      <option value="">-
      <?php _e('Select Date Format', $this -> plugin_name); ?>
      -</option>
      <?php foreach ($dateformats as $format) : ?>
      <option value="<?php echo $format; ?>" <?php echo (!empty($dateformat) && $dateformat == $format) ? 'selected="selected"' : ''; ?>>
      <?php echo date($format, time()); ?>
      </option>
      <?php endforeach; ?>
    </select>
    </label>
  </p>
  <p>
    <label for="mref_widget_<?php echo $number; ?>_max_length">
    <?php _e('Max Length', $this -> plugin_name); ?>
    :
    <input style="width:45px; text-align:center;" type="text" id="mref_widget_<?php echo $number; ?>_max_length" size="5" name="mref-widget[<?php echo $number; ?>][max_length]" value="<?php echo $max_length; ?>" />
    <?php _e('characters', $this -> plugin_name); ?>
    <br/>
    <small>leave empty to show full titles</small> </label>
  </p>
  <p>
    <label for="mref_widget_<?php echo $number; ?>_exclude">
    <?php _e('Exclude', $this -> plugin_name); ?>
    :
    <input class="widefat" id="mref_widget_<?php echo $number; ?>_exclude" type="text" name="mref-widget[<?php echo $number; ?>][exclude]" value="<?php echo $exclude; ?>" />
    <br/>
    <small>Enter comma separated IDs</small> </label>
  </p>
  <?php

		

		if (!empty($recent)) {

			?>
  <script type="text/javascript">

			change_display('<?php echo $recent; ?>','<?php echo $number; ?>');

		</script>
  <?php

		}?>
</div>
<div class="locationpanel" style="display:<?php if ($widget_values['call'] == "location") { echo "block"; } else { echo "none"; } ?>;"> <br />
  <div class="cat">
    <p><?php echo $allcategories; ?>
      <input type="checkbox" <?php echo (!empty($allcategories) && $allcategories == "Y") ? 'checked="checked"' : ''; ?> value="Y" name="mref-widget[<?php echo $number; ?>][allcategories]" id="mref_widget_<?php echo $number; ?>_allcategories">
      <?php _e('All Categories | Only those checked Categories.', $this -> plugin_name); ?>
    </p>
    <hr />
    <ul id="cate" style="">
      <?php $categories = get_categories(array('number' => false, 'hide_empty' => false, 'child_of' => 0, 'order' => "ASC", 'orderby' => "name")); ?>
      <?php if (!empty($categories)) : ?>
      <?php foreach ($categories as $category) : ?>
      <li>
	  
        <input type="checkbox"	
<?php if(is_array($postcategory)) { ?>
	<?php foreach ($postcategory as $postcat) : ?>

		<?php 

			if($postcat == $category -> cat_ID) {

				$sel = 'checked="checked"';

			} else {

				$sel = '';

			}

		?>

	  <?php echo $sel; ?>

	  <?php endforeach; ?>
<?php } ?>
	  value="<?php echo $category -> cat_ID; ?>" name="mref-widget[<?php echo $number; ?>][postcategory][]" id="mref_widget_<?php echo $number; ?>_category-<?php echo $category -> cat_ID; ?>">
        <?php echo $category -> cat_name; ?>
        (
		<?php echo $this -> wt_get_category_count($category -> cat_ID); ?>
        <?php /*echo count(get_categories(array('child_of' => $category -> cat_ID, 'number' => false, 'hide_empty' => false)));*/ ?>
        )</li>
      <?php endforeach; ?>
      <?php endif; ?>
    </ul>
  </div>
  <br />
  <p><strong>Default :</strong> If you select nothing here, your widget will be displayed in position throughout your entire site, like other widgets. if you select "All Categories", it will be displayed only on Post Categories. If you Select only certain categories in the pop-down - this widget will only appear in those categories.</p>
  <p class="more">More Location Options Coming in Pro Version</p>
  <a target="_blank" href="http://www.webfadds.com/wordpress-services/plugins/">
  <div class="btmimg">&nbsp;</div>
  </a>
  <p>Watch for our "Pro Version" of Max-Ref Widgets Coming Soon with More Locations, More Features. And a third tab full of more helpful options....<a target="_blank" href="http://www.webfadds.com/wordpress-services/plugins/">WebFadds.com</a></p>
</div>
<script type="text/javascript">
/*
jQuery(document).ready(function($) {
	$(".display").addClass("active");
	$('.display').click(function()	{
		$(".display").addClass("active");
		$(".location").removeClass("active");
		$('.displaypanel').show();
		$('.locationpanel').hide();
	});
	$('.display').hover(function () {
		$(".display").addClass("hactive");
	},
	function () {
		$(".display").removeClass("hactive");
	});
	$('.location').click(function(){
		$(".location").addClass("active");
		$(".display").removeClass("active");
		$('.locationpanel').show();
		$('.displaypanel').hide();
	});
	$('.location').hover(function () {
		$(".location").addClass("hactive");
	},
	function () {
		$(".location").removeClass("hactive");
	});
});
*/
</script>
<?php
	}
	function mref_widget_register() {		
		if (function_exists('register_sidebar_widget')) {
			if (!$options = get_option('mref-widget')) {
				$options = array();
			}

			$widget_options = array('classname' => 'mref-widget', 'description' => __('Output comments, links, categories, posts and more in your sidebar(s)', $this -> plugin_name));	
			$control_options = array('id_base' => 'mref', 'width' => 350, 'height' => 300);	
			$name = __('MaxRef Widgets', $this -> plugin_name);

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