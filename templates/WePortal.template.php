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

function template_weportal_admin_providers_index()
{
    global $settings, $txt, $context, $scripturl;

    foreach ($context['wep_holders'] as $holder)
    {
        echo '
            <header class="cat">', $txt['wep_holder_' . $holder['id']], '</header>
            <table class="table_grid cs0" style="width: 100%;">
                <tr class="catbg">
                    <th scope="col" style="width: 8%;">', $txt['wep_id'], '</th>
                    <th scope="col">', $txt['wep_provider'], '</th>
                    <th scope="col">', $txt['wep_title'], '</th>
                    <th scope="col">', $txt['wep_actions'], '</th>
                </tr>';
        $alt = false;
        foreach ($holder['providers'] as $provider)
        {
            $alt = !$alt;
            echo '
                <tr class="windowbg', $alt ? '2' : '', '">
                    <td>', $provider['id'], '</td>
                    <td>', $txt['wep_provider_' . $provider['controller']], '</td>
                    <td>', $provider['title'], '</td>
                    <td><a href="', $scripturl, '?action=admin;area=providers;sa=toggle;id=', $provider['id'], '">', $txt['wep_' . ($provider['enabled'] ? 'disable' : 'enable')], '</a></td>
               </tr>';
        }
        echo '
            </table>';
   }
}

function template_weportal_admin_providers_add_step1()
{
    global $settings, $txt, $context, $scripturl;

    echo '
        <header class="cat">', $txt['wep_select_provider_holder'], '</header>
        <form action="', $scripturl, '?action=admin;area=providers;sa=add" method="post">
            <div class="windowbg2 wrc">
                <dl class="settings">
                   <dt>
                        <label><span>', $txt['wep_holder'], '</span></label>
                   </dt>
                   <dd>
                        <select name="holder">';
    foreach ($context['wep_holders'] as $holder)
        echo '
                            <option value="', $holder, '">', $txt['wep_holder_' . $holder], '</option>';
    echo '
                        </select>
                   </dd>
                   <dt>
                        <label><span>', $txt['wep_provider'], '</span></label>
                   </dt>
                   <dd>
                        <select name="provider">';
    foreach ($context['wep_providers'] as $provider)
        echo '
                            <option value="', $provider, '">', $txt['wep_provider_' . $provider], '</option>';
    echo '
                        </select>
                   </dd>
               </dl>
               <hr />
               <div class="righttext">
                    <input type="submit" name="step" value="', $txt['submit'], '" class="submit" />
               </div>
           </div>
        </form>';
}

function template_weportal_admin_providers_add_step2()
{
    global $context, $settings, $txt, $scripturl;

    echo '
        <form action="', $scripturl, '?action=admin;area=providers;sa=add" method="post">
        <header class="cat">', $txt['wep_select_provider_parameters'], '</header>
            <input type="hidden" name="holder" value="', $context['wep_holder'], '" />
            <input type="hidden" name="provider" value="', $context['wep_provider'], '" />

            <div class="windowbg2 wrc">
                <dl class="settings">
                    <dt>
                        <label><span>', $txt['wep_title'], '</span></label>
                    </dt>
                    <dd>
                        <input type="text" name="title" size="80" value="" />
                    </dd>
                    <dt>
                        <label><span>', $txt['wep_groups'], '</span></label>
                    </dt>
                    <dd>';
    foreach ($context['wep_groups'] as $group)
        echo '
                        <input type="checkbox" name="groups[]" value="', $group['id_group'], '" /> ', $group['group_name'], '<br />';
    echo '
                    </dd>
                    <dt>
                        <label><span>', $txt['wep_adjustable'], '</span></label>
                    </dt>
                    <dd>
                        <input type="checkbox" name="adjustable" value="1" />
                    </dd>
                </dl>
                <hr />
                <dl class="settings">';
    foreach ($context['wep_params'] as $key => $param)
    {
        echo '
                    <dt>
                        <label><span>', $param['label'], '</span></label>
                    </dt>
                    <dd>';
        template_weportal_field('provider_' . $key, $param);
        echo '
                    </dd>';
    }

    echo '
               </dl>
               <hr />
               <dl class="settings">';
    foreach ($context['wep_holder_params'] as $key => $param)
    {
        echo '
                    <dt>
                        <label><span>', $param['label'], '</span></label>
                    </dt>
                    <dd>';
        template_weportal_field('provider_' . $key, $param);
        echo '
                    </dd>';
    }

    echo '
               </dl>
               <hr />
               <div class="righttext">
                    <input type="submit" name="save" value="', $txt['submit'], '" class="submit" />
               </div>
            </div>
        </form>';
}

function template_weportal_field($key, $param)
{
        switch ($param['type'])
        {
            case 'text':
                echo '
                        <input name="', $key, '" type="text"', $param['subtype'] == 'int' ? ' size="10"' : '', ' value="" />';
                break;
            case 'textbox':
                echo '
                        <textarea name="', $key, '" cols="60" rows="8"></textarea>';
                break;
            case 'select';
                echo '
                        <select name="', $key, '">';
                foreach ($param['options'] as $k => $v)
                    echo '
                            <option value="', $k, '">', $v, '</option>';
                echo '
                        </select>';
                break;
        }
 }
?>