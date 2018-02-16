<?php
/**************************************************
  googletagged mod v2.1- googletagged.template.php
**************************************************/

function template_main()
{
	global $context, $txt, $scripturl, $modSettings;

	// start the table
	echo '
	<div class="title_bar">
		<h3 class="titlebg">
			<span style="margin: 0 auto;">', sprintf($txt['googletagged_random'], $modSettings['googletagged_limit_max'], $context['googletagged_total']), '</span>
		</h3>
	</div>
	<div class="windowbg2">
		<span class="topslice"><span></span></span>
		<div class="content">
			<div style="text-align: center;">';

	// start our tag cloud
	if(isset($context['googletagged']))
	{
		$i = 1 ;
		// write out our tags
		foreach($context['googletagged'] as $key => $row)
		{
			echo '
				<a href="', $scripturl , '?action=tagged;id=', $row['id_tag'] ,';tag=', $row['tag'] ,'" style="font-size: '.$row['size'].'%;" title="', $row['text'] ,'">', $row['text'] ,'</a>';
			// increase counter until we may need to break
			// if divisable by 10 - new line
			echo (($i % 10) == 0) ? '<br />' : '';
			$i++;
		}
		// tidy up
		unset($i,$key,$row);
	} else {
		// no tags, so tell the user
		echo $txt['googletagged_empty'];
	}

	// end the table
	echo '
			</div>
		</div>
		<span class="botslice"><span></span></span>
	</div>';
}

// results template
function template_results()
{
	global $context, $scripturl, $txt;

		global $context, $settings, $options, $txt, $scripturl, $modSettings;

	echo '
	<div class="pagesection">
		<div class="pagelinks align_left">', $txt['pages'], ': ', $context['page_index'], !empty($modSettings['topbottomEnable']) ? $context['menu_separator'] . '&nbsp;&nbsp;<a href="#bot"><strong>' . $txt['go_down'] . '</strong></a>' : '', '</div>
	</div>
	<div class="tborder topic_table" id="messageindex">
		<table class="table_grid" cellspacing="0">
			<thead>
				<tr class="catbg">';
			// now write each topic row			
			if (!empty($context['topics']))
			{
				// headers  first
				echo '
					<th scope="col" class="first_th" width="8%" colspan="2">&nbsp;</th>
					<th scope="col" class="lefttext"><a href="', $context['easy_sort'] ,'sort=subject', $context['sort_by'] == 'subject' && $context['sort_direction'] == 'up' ? ';desc' : '', '">', $txt['subject'], $context['sort_by'] == 'subject' ? ' <img src="' . $settings['images_url'] . '/sort_' . $context['sort_direction'] . '.gif" alt="" />' : '', '</a> / <a href="', $context['easy_sort'] ,'sort=starter', $context['sort_by'] == 'starter' && $context['sort_direction'] == 'up' ? ';desc' : '', '">', $txt['started_by'], $context['sort_by'] == 'starter' ? ' <img src="' . $settings['images_url'] . '/sort_' . $context['sort_direction'] . '.gif" alt="" />' : '', '</a></th>
					<th scope="col" class="smalltext center" width="8%"><a href="', $context['easy_sort'] ,'sort=hits', $context['sort_by'] == 'hits' && $context['sort_direction'] == 'up' ? ';desc' : '', '">', $txt['googletagged_hits'], $context['sort_by'] == 'hits' ? ' <img src="' . $settings['images_url'] . '/sort_' . $context['sort_direction'] . '.gif" alt="" />' : '', '</a></th>
					<th scope="col" class="smalltext center" width="14%"><a href="', $context['easy_sort'] ,'sort=replies', $context['sort_by'] == 'replies' && $context['sort_direction'] == 'up' ? ';desc' : '', '">', $txt['replies'], $context['sort_by'] == 'replies' ? ' <img src="' . $settings['images_url'] . '/sort_' . $context['sort_direction'] . '.gif" alt="" />' : '', '</a> / <a href="', $context['easy_sort'] ,'sort=views', $context['sort_by'] == 'views' && $context['sort_direction'] == 'up' ? ';desc' : '', '">', $txt['views'], $context['sort_by'] == 'views' ? ' <img src="' . $settings['images_url'] . '/sort_' . $context['sort_direction'] . '.gif" alt="" />' : '', '</a></th>
					<th scope="col" class="smalltext last_th" width="22%"><a href="', $context['easy_sort'] ,'sort=last_post', $context['sort_by'] == 'last_post' && $context['sort_direction'] == 'up' ? ';desc' : '', '">', $txt['last_post'], $context['sort_by'] == 'last_post' ? ' <img src="' . $settings['images_url'] . '/sort_' . $context['sort_direction'] . '.gif" alt="" />' : '', '</a></th>';
			}
			else
				echo '
					<th scope="col" class="smalltext first_th" width="8%">&nbsp;</th>
					<th class="smalltext" colspan="3"><strong>', $txt['msg_alert_none'], '</strong></th>
					<th scope="col" class="smalltext last_th" width="8%">&nbsp;</th>';
	echo '
				</tr>
			</thead>
			<tbody>';
	
	// write each topic
	foreach ($context['topics'] as $topic)
	{
		// We start with locked and sticky topics.
		if ($topic['is_sticky'] && $topic['is_locked'])
			$color_class = 'stickybg locked_sticky';
		// Sticky topics should get a different color, too.
		elseif ($topic['is_sticky'])
			$color_class = 'stickybg';
		// Locked topics get special treatment as well.
		elseif ($topic['is_locked'])
			$color_class = 'lockedbg';
		// Last, but not least: regular topics.
		else
			$color_class = 'windowbg';

		// Some columns require a different shade of the color class.
		$alternate_class = $color_class . '2';

			echo '
				<tr>
					<td class="icon1 ', $color_class, '">
						<img src="', $settings['images_url'], '/topic/', $topic['class'], '.gif" alt="" />
					</td>
					<td class="icon2 ', $color_class, '">
						<img src="', $topic['first_post']['icon_url'], '" alt="" />
					</td>
					<td class="subject ', $alternate_class, '">
						<div>
							', $topic['is_sticky'] ? '<strong>' : '', $topic['first_post']['link'], $topic['is_sticky'] ? '</strong>' : '', '
							<p>', $txt['started_by'], ' ', $topic['first_post']['member']['link'], '
								<small id="pages' . $topic['id'] . '">', $topic['pages'], '</small>
							</p>
						</div>
					</td>
					<td class="stats ', $color_class, '">
						', $topic['hits'], ' ', $txt['googletagged_hits'], '
					</td>
					<td class="stats ', $color_class, '">
						', $topic['replies'], ' ', $txt['replies'], '
						<br />
						', $topic['views'], ' ', $txt['views'], '
					</td>
					<td class="lastpost ', $color_class, '">
						<a href="', $topic['last_post']['href'], '"><img src="', $settings['images_url'], '/icons/last_post.gif" alt="', $txt['last_post'], '" title="', $txt['last_post'], '" /></a>
						', $topic['last_post']['time'], '<br />
						', $txt['by'], ' ', $topic['last_post']['member']['link'], '
					</td>
				</tr>';
		}

	echo '
			</tbody>
		</table>
	</div>
	<a id="bot"></a>
	<div class="pagesection">
		<div class="pagelinks">', $txt['pages'], ': ', $context['page_index'], !empty($modSettings['topbottomEnable']) ? $context['menu_separator'] . '&nbsp;&nbsp;<a href="#top"><strong>' . $txt['go_up'] . '</strong></a>' : '', '</div>
	</div><br />
	<div class="tborder" id="topic_icons">
		<div class="description">
			<p class="floatleft smalltext">', !empty($modSettings['enableParticipation']) && $context['user']['is_logged'] ? '
				<img src="' . $settings['images_url'] . '/topic/my_normal_post.gif" alt="" align="middle" /> ' . $txt['participation_caption'] . '<br />' : '', '
				<img src="' . $settings['images_url'] . '/topic/normal_post.gif" alt="" align="middle" /> ' . $txt['normal_topic'] . '<br />
				<img src="' . $settings['images_url'] . '/topic/hot_post.gif" alt="" align="middle" /> ' . sprintf($txt['hot_topics'], $modSettings['hotTopicPosts']) . '<br />
				<img src="' . $settings['images_url'] . '/topic/veryhot_post.gif" alt="" align="middle" /> ' . sprintf($txt['very_hot_topics'], $modSettings['hotTopicVeryPosts']) . '
			</p>
			<p class="smalltext">
				<img src="' . $settings['images_url'] . '/icons/quick_lock.gif" alt="" align="middle" /> ' . $txt['locked_topic'] . '<br />' . ($modSettings['enableStickyTopics'] == '1' ? '
				<img src="' . $settings['images_url'] . '/icons/quick_sticky.gif" alt="" align="middle" /> ' . $txt['sticky_topic'] . '<br />' : '') . ($modSettings['pollMode'] == '1' ? '
				<img src="' . $settings['images_url'] . '/topic/normal_poll.gif" alt="" align="middle" /> ' . $txt['poll'] : '') . '
			</p>
			<br class="clear" />
		</div>
	</div>';

}

function template_ShowGoogleTaggedAdmin()
{
	global $context, $scripturl, $txt, $modSettings;

	echo '
	<form method="post" action="' . $scripturl . '?action=admin;area=modsettings;sa=googletagged;', $context['session_var'], '=', $context['session_id'], ';save">
		<div class="title_bar">
			<h3 class="titlebg">
				<span class="ie6_header floatleft">
					', $txt['googletagged_admin'], '
				</span>
			</h3>
		</div>
		<div class="cat_bar">
			<h3 class="catbg">
				<span class="ie6_header floatleft">
					', $txt['googletagged_admin_settings'], '
				</span>
			</h3>
		</div>
		<div class="windowbg2">
			<span class="topslice"><span></span></span>
			<div class="content">
				<dl class="settings">
					<dt><span><label for="googletagged">', $txt['googletagged_googletagged'], '</label></span></dt>
					<dd><input type="checkbox" id="googletagged" name="googletagged"', $modSettings['googletagged'] == 1 ? ' checked="checked"' : '' ,' /></dd>
					
					<dt><span><label for="googletagged_min_length">', $txt['googletagged_min_length'], '</label></span></dt>
					<dd><input type="text" id="googletagged_min_length" name="googletagged_min_length" value="', !empty($modSettings['googletagged_min_length']) ? $modSettings['googletagged_min_length'] : '','" /></dd>
					
					<dt><span><label for="googletagged_max_length">', $txt['googletagged_max_length'], '</label></span></dt>
					<dd><input type="text" id="googletagged_max_length" name="googletagged_max_length" value="', !empty($modSettings['googletagged_max_length']) ? $modSettings['googletagged_max_length'] : '','" /></dd>
					
					<dt><span><label for="googletagged_limit_max">', $txt['googletagged_limit_max'], '</label></span></dt>
					<dd><input type="text" id="googletagged_limit_max" name="googletagged_limit_max" value="', !empty($modSettings['googletagged_limit_max']) ? $modSettings['googletagged_limit_max'] : '','" /></dd>
					
					<dt><span><label for="googletagged_limit_max_display">', $txt['googletagged_limit_max_display'], '</label></span></dt>
					<dd><input type="text" id="googletagged_limit_max_display" name="googletagged_limit_max_display" value="', !empty($modSettings['googletagged_limit_max_display']) ? $modSettings['googletagged_limit_max_display'] : '','" /></dd>
					
					<dt><span><label for="googletagged_together">'. $txt['googletagged_together'] . '</label></span></dt>
					<dd><input type="checkbox" id="googletagged_together" name="googletagged_together"', (!empty($modSettings['googletagged_together']) ? ' checked="checked"' : '') ,' /></dd>
					
					<dt>
						<a href="', $scripturl, '?action=admin;area=modsettings;sa=googletagged;', $context['session_var'], '=', $context['session_id'], ';reset">', $txt['googletagged_admin_reset'], '</a> | 
						<a href="', $scripturl, '?action=admin;area=modsettings;sa=googletagged;', $context['session_var'], '=', $context['session_id'], ';optimize">', $txt['googletagged_admin_optimize'], '</a>
					</dt>
				</dl>
			</div>
			<span class="botslice"><span></span></span>
		</div><br />';

		// ban words add/remove table
		echo '
		<div class="title_bar">
			<h3 class="titlebg">
				<span class="ie6_header floatleft">
					', $txt['googletagged_adminsbannedwords'], '
				</span>
			</h3>
		</div>
		<div class="cat_bar">
			<h3 class="catbg">
				<span class="ie6_header floatleft">
					', $txt['googletagged_admin_addbannedwords'], '
				</span>
			</h3>
		</div>
		<div class="windowbg2">
			<span class="topslice"><span></span></span>
			<div class="content">
				<p>', $txt['googletagged_admin_addwordban'], '</p>
				<div style="margin-top: 1ex;"><input type="text" name="googletagged_addwordban[]" value="" class="input_text" /></div>
				<noscript>
					<div style="margin-top: 1ex;"><input type="text" name="googletagged_addwordban[]" size="20" class="input_text" /></div>
				</noscript>
				<div id="moreBannedTag"></div><div style="margin-top: 1ex; display: none;" id="moreBannedTag_link"><a href="#;" onclick="addNewBannedTag(); return false;">', $txt['googletagged_clickadd'], '</a></div>
				<script type="text/javascript"><!-- // --><![CDATA[
					document.getElementById("moreBannedTag_link").style.display = "";

					function addNewBannedTag()
					{
						setOuterHTML(document.getElementById("moreBannedTag"), \'<div style="margin-top: 1ex;"><input type="text" name="googletagged_addwordban[]" size="20" class="input_text" /><\' + \'/div><div id="moreBannedTag"><\' + \'/div>\');
					}
				// ]]></script>
			</div>
			<span class="botslice"><span></span></span>
		</div><br />
		<div class="cat_bar">
			<h3 class="catbg">
				<span class="ie6_header floatleft">
					', $txt['googletagged_admin_removebannedwords'], '
				</span>
			</h3>
		</div>
		<div class="windowbg2">
			<span class="topslice"><span></span></span>
			<div class="content">
				<dl class="settings_gt">';
			// now add all banned words
			if(empty($context['googletagged_banned'])) {
				echo '
					<dt class="windowbg">', $txt['googletagged_admin_nobannedwords'], '</dt>';
			}
			else {
				foreach($context['googletagged_banned'] as $id_tag => $tag)
				{
					echo '
					<dt><a href="', $scripturl ,'?action=admin;area=modsettings;sa=googletagged;id=', $id_tag ,';tag=', $tag ,';unban;', $context['session_var'], '=', $context['session_id'], '">', $tag ,'</a></dt>';
				}
			}
				echo '
				</dl>
			</div>
			<span class="botslice"><span></span></span>
		</div><br />
		<div style="margin:0 auto;text-align:center;">
			<input type="submit" name="save" value="', $txt['save'], '" style="margin: 2px;" class="button_submit" />
		</div>
	</form>';
}