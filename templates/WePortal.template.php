<?php
/****************************************************************
* WePortal														*
* © Shitiz "Dragooon" Garg										*
*****************************************************************
* WePortal.template.php - Main template for our portal			*
*****************************************************************
* Users of this software are bound by the terms of the			*
* WePortal license. You can view it in the license_wep.txt		*
* file															*
*																*
* For support and updates, don't come to me						*
****************************************************************/

function template_weportal_bar_left_before()
{
	global $context, $txt, $scripturl;

	echo '
		<ul class="wep_left" data-portal-bar="true" data-bar-side="left">';

	template_weportal_output_blocks($context['weportal_left_blocks']);

	echo '
		</ul>';
}

function template_weportal_bar_left_after()
{
}

function template_weportal_bar_right_before()
{
	global $context, $txt, $scripturl;

	echo '
		<ul class="wep_right" data-portal-bar="true" data-bar-side="right">';

	template_weportal_output_blocks($context['weportal_right_blocks']);

	echo '
		</ul>';
}

function template_weportal_bar_right_after()
{
	echo '
		<br style="clear: both;" />';
}

// This template outputs the blocks for any bar
function template_weportal_output_blocks($blocks)
{
	global $settings;

	$first = true;
	foreach ($blocks as $block)
	{
		echo '
			<li class="wep_block', $first ? ' wep_block_first' : '', !$block->enabled() ? ' wep_hidden' : '', '" data-portal-block="true" data-block-hidden="', $block->enabled() ? 'false' : 'true', '" data-block-id="', $block->id(), '">
				<we:title>', $block->title(), '</we:title><hr />
				<dl>', $block->render(), '</dl>
				<img class="wep_disable" src="', $settings['images_url'], '/icons/delete.gif" />
				<img class="wep_enable" src="', $settings['images_url'], '/icons/field_valid.gif" />
			</li>';

		$first = false;
	}
}

// The template's for recent topics/posts block
function template_weblock_recent($posts)
{
	global $txt;

	echo '
		<ul class="wep_block_recent">';
	foreach ($posts as $post)
	{
		echo '
			<li>
				<span class="title_link">', $post['link'], '</span><br />
				<p class="smalltext">
					<img src="', $post['poster']['avatar'], '" align="left" width="40" />
					', $post['preview'], '
				</p><div class="smalltext"><strong>', ucwords($txt['by']), '</strong> ', $post['poster']['link'], '</div><hr />
			</li>';
	}
	echo '
		</ul>';
}
?>