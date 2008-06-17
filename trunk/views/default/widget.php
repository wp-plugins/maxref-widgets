<?= $args['before_widget']; ?>
	<?= $args['before_title']; ?><?= (!empty($args['titlelink']) && $args['titlelink'] == "Y") ? '<a href="' . $args['titlelinkurl'] . '" title="' . $args['title'] . '">' : ''; ?><?= $args['title']; ?><?= (!empty($args['titlelink']) && $args['titlelink'] == "Y") ? '</a>' : ''; ?><?= $args['after_title']; ?>
	<?php if (!empty($args['parent'])) : ?>
		<ul>
			<li><a href="<?= $args['parent']['href']; ?>" title="<?= $args['parent']['title']; ?>"><?= $args['parent']['title']; ?></a>
	<?php endif; ?>
	<ul>
		<?php foreach ($items as $item) : ?>
			<li>
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
<?= $args['after_widget']; ?>