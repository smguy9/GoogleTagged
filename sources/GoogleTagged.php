<?php
/**************************************************
	GoogleTagged Mod v2.1 - GoogleTaggged.php
**************************************************/
if (!defined('SMF'))
	die('Hacking attempt...');

function GoogleTagged()
{
	global $scripturl, $user_info, $context, $txt, $mbname, $smcFunc;
	global $modSettings, $settings, $board, $sourcedir;

	// Load the template and language files
	loadTemplate('GoogleTagged');
	loadLanguage('GoogleTagged');
	
	if (allowedTo('googletagged_view') && empty($modSettings['googletagged']))
		fatal_lang_error('googletagged_disabled');
	
	// To tell us when we are done!
	$done = false;
	
	// Do we have a tag id?
	if (!empty($_REQUEST['id']))
	{
		// Make it safe
		$idtag = (int) $_REQUEST['id'];
		$_REQUEST['start'] = (int) $_REQUEST['start'];
		
		// Query the id_tag to get the tag name
		$query = $smcFunc['db_query']('', '
			SELECT tag
			FROM {db_prefix}googletagged
			WHERE id_tag = {int:tag}
			LIMIT 1',
			array(
				'tag' => $idtag,
			)
		);
		// Found the tag?
		if ($smcFunc['db_num_rows']($query) != 0)
		{
			$row = $smcFunc['db_fetch_assoc']($query);
			// Get the tag
			$context['googletagged_tag'] = $row['tag'];
			$context['googletagged_text'] = str_replace("+", " ", $row['tag']);
			// Tidy up
			unset($row);
			$smcFunc['db_free_result']($query);
			
			// Form the page title with our search
			$context['page_title'] = $mbname .' - '. $txt['googletagged_search'] . $context['googletagged_text'];
			$context['linktree'][] = array(
				'url' => $scripturl . '?action=tagged',
				'name' => $txt['googletagged']
			);
			$context['linktree'][] = array(
				'url' => $scripturl . '?action=tagged;id=' . $idtag . ';tag=' . $context['googletagged_text'],
				'name' => $txt['googletagged_search'] . '"' . $context['googletagged_text'] . '"'
			);
		
			// Load the icons to use in a bit
			$default_icons = array('xx', 'thumbup', 'thumbdown', 'exclamation', 'question', 'lamp', 'smiley', 'angry', 'cheesy', 'grin', 'sad', 'wink', 'moved', 'recycled', 'wireless', 'clip');
			$context['icon_sources'] = array();
			foreach ($default_icons as $icon)
				$context['icon_sources'][$icon] = 'images_url';

			// Array for sorting - default = hits
			$sort_methods = array(
				'subject' => 'ms.subject',
				'starter' => 'ms.poster_name',
				'replies' => 't.num_replies',
				'views' => 't.num_views',
				'first_post' => 't.id_topic',
				'last_post' => 't.id_last_msg',
				'hits' => 'g.hits',
			);

			// Work out sort method
			if (!isset($_REQUEST['sort']) || !isset($sort_methods[$_REQUEST['sort']]))
			{
				$context['sort_by'] = 'hits';
				$_REQUEST['sort'] = 'g.hits';
				$ascending = isset($_REQUEST['asc']);
				$context['querystring_sort_limits'] = $ascending ? ';asc' : '';
			}
			else
			{
				$context['sort_by'] = $_REQUEST['sort'];
				$_REQUEST['sort'] = $sort_methods[$_REQUEST['sort']];
				$ascending = !isset($_REQUEST['desc']);
				$context['querystring_sort_limits'] = ';sort=' . $context['sort_by'] . ($ascending ? '' : ';desc');
			}
			$context['sort_direction'] = $ascending ? 'up' : 'down';
			
			// Get ready for pagination
			$query = $smcFunc['db_query']('', '
				SELECT COUNT(DISTINCT t.id_topic)
				FROM {db_prefix}topics AS t, {db_prefix}googletagged as g
				WHERE g.tag = {string:tag}
					AND g.status != {int:status}
					AND g.id_topic = t.id_topic',
				array(
					'tag' => $context['googletagged_tag'],
					'status' => 0,
				)
			);
			list ($num_topics) = $smcFunc['db_fetch_row']($query);
			$context['page_index'] = constructPageIndex($scripturl . '?action=tagged;id='. $idtag .';tag='. $context['googletagged_tag'], $_REQUEST['start'], $num_topics, $modSettings['defaultMaxTopics']);
			$context['easy_sort'] = $scripturl . '?action=tagged;id='. $idtag .';tag='. $context['googletagged_tag'].';';
			$context['current_page'] = $_REQUEST['start'] / $modSettings['defaultMaxTopics'];
			// Tidy up
			$smcFunc['db_free_result']($query);

			// Results sort initially by hits where tag hasnt been banned
			$query = $smcFunc['db_query']('', '
				SELECT
					g.id_tag, g.tag, g.status, g.hits, g.id_topic,
					t.id_first_msg, t.id_last_msg, t.id_board, t.id_poll, t.is_sticky, t.locked, t.num_replies, t.num_views,
					b.name AS bname,
					ms.id_member, ms.id_topic, ms.poster_time AS first_poster_time, ms.poster_time, ms.subject AS first_subject, 
					ms.id_member AS id_first_member, ms.poster_name AS first_poster_name,
					ms.icon AS first_icon, ms.smileys_enabled AS first_smileys,
					ml.id_member AS id_last_member, ml.poster_time AS last_poster_time, ml.modified_time AS last_modified_time, 
					ml.id_topic, ml.subject AS last_subject, ml.poster_name AS lastPosterName, ml.icon AS last_icon, ml.smileys_enabled AS last_smileys
				FROM ({db_prefix}googletagged as g, {db_prefix}boards AS b, {db_prefix}topics as t,
					{db_prefix}messages AS ms, {db_prefix}messages AS ml)
				WHERE tag = {string:tag}
					AND g.status != {int:status}
					AND b.id_board = t.id_board
					AND g.id_topic = t.id_topic
					AND ms.id_msg = t.id_first_msg
					AND ml.id_msg = t.id_last_msg
					AND {query_see_board}
				ORDER BY ' . $_REQUEST['sort'] . ($ascending ? '' : ' DESC') . '
				LIMIT {int:start}, {int:max}',
				array(
					'tag' => $context['googletagged_tag'],
					'status' => 0,
					'start' => $_REQUEST['start'],
					'max' => $modSettings['defaultMaxTopics'],
				)
			);
			// Prepare our array for topics
			$context['topics'] = array();
			$topic_ids = array();
			
			// So did we find some topics googletagged with this tag?
			if ($num_topics != 0)
			{
				// Cycle through them and do some sorting, before adding to array
				while ($row = $smcFunc['db_fetch_assoc']($query))
				{
					$topic_ids[] = $row['id_topic'];
					
					// Decide how many pages the topic should have.
					if ($row['num_replies'] + 1 > $modSettings['defaultMaxMessages'])
					{
						$pages = '&#171; ';

						// We can't pass start by reference.
						$start = -1;
						$pages .= constructPageIndex($scripturl . '?topic=' . $row['id_topic'] . '.%1$d', $start, $row['num_replies'] + 1, $modSettings['defaultMaxMessages'], true);

						// If we can use all, show all.
						if (!empty($modSettings['enableAllMessages']) && $row['num_replies'] + 1 < $modSettings['enableAllMessages'])
							$pages .= ' &nbsp;<a href="' . $scripturl . '?topic=' . $row['id_topic'] . '.0;all">' . $txt['all'] . '</a>';
						$pages .= ' &#187;';
					}
					else
						$pages = '';
					
					
					
					// We need to check the topic icons exist...
					if (empty($modSettings['messageIconChecks_disable']))
					{
						if (!isset($context['icon_sources'][$row['first_icon']]))
							$context['icon_sources'][$row['first_icon']] = file_exists($settings['theme_dir'] . '/images/post/' . $row['first_icon'] . '.gif') ? 'images_url' : 'default_images_url';
						if (!isset($context['icon_sources'][$row['last_icon']]))
							$context['icon_sources'][$row['last_icon']] = file_exists($settings['theme_dir'] . '/images/post/' . $row['last_icon'] . '.gif') ? 'images_url' : 'default_images_url';
					}
					else
					{
						if (!isset($context['icon_sources'][$row['first_icon']]))
							$context['icon_sources'][$row['first_icon']] = 'images_url';
						if (!isset($context['icon_sources'][$row['last_icon']]))
							$context['icon_sources'][$row['last_icon']] = 'images_url';
					}

					$topicEditedTime = $row['last_poster_time'] > $row['last_modified_time'] ? $row['last_poster_time'] : $row['last_modified_time'];

					// And build the array.
					$context['topics'][$row['id_topic']] = array(
						'id' => $row['id_topic'],
						'first_post' => array(
							'member' => array(
								'name' => $row['first_poster_name'],
								'id' => $row['id_first_member'],
								'href' => $scripturl . '?action=profile;u=' . $row['id_first_member'],
								'link' => !empty($row['id_first_member']) ? '<a href="' . $scripturl . '?action=profile;u=' . $row['id_first_member'] . '" title="' . $txt['profile_of'] . ' ' . $row['first_poster_name'] . '">' . $row['first_poster_name'] . '</a>' : $row['first_poster_name']
							),
							'time' => timeformat($row['first_poster_time']),
							'timestamp' => $row['first_poster_time'],
							'subject' => $row['first_subject'],
							'icon' => $row['first_icon'],
							'icon_url' => $settings[$context['icon_sources'][$row['first_icon']]] . '/post/' . $row['first_icon'] . '.gif',
							'href' => $scripturl . '?topic=' . $row['id_topic'] . '.0;topicseen',
							'link' => '<a href="' . $scripturl . '?topic=' . $row['id_topic'] . '.0;topicseen">' . $row['first_subject'] . '</a>'
						),
						'last_post' => array(
							'member' => array(
								'name' => $row['lastPosterName'],
								'id' => $row['id_last_member'],
								'href' => $scripturl . '?action=profile;u=' . $row['id_last_member'],
								'link' => !empty($row['id_last_member']) ? '<a href="' . $scripturl . '?action=profile;u=' . $row['id_last_member'] . '">' . $row['lastPosterName'] . '</a>' : $row['lastPosterName']
							),
							'time' => timeformat($row['last_poster_time']),
							'timestamp' => $row['last_poster_time'],

							'href' => $scripturl . '?topic=' . $row['id_topic'] . '.0;topicseen#new',
							'link' => '<a href="' . $scripturl . '?topic=' . $row['id_topic'] . '.0;topicseen#new">' . $row['last_subject'] . '</a>'
						),
						'href' => $scripturl . '?topic=' . $row['id_topic'] . '.0;topicseen#new',
						'link' => '<a href="' . $scripturl . '?topic=' . $row['id_topic'] . '.0;topicseen#new">' . $row['first_subject'] . '<</a>',
						'is_sticky' => !empty($modSettings['enableStickyTopics']) && !empty($row['is_sticky']),
						'is_locked' => !empty($row['locked']),
						'is_poll' => $modSettings['pollMode'] == '1' && $row['id_poll'] > 0,
						'is_hot' => $row['num_replies'] >= $modSettings['hotTopicPosts'],
						'is_very_hot' => $row['num_replies'] >= $modSettings['hotTopicVeryPosts'],
						'is_posted_in' => false,
						'icon' => $row['first_icon'],
						'subject' => $row['first_subject'],
						'pages' => $pages,
						'replies' => $row['num_replies'],
						'views' => $row['num_views'],
						'hits' => $row['hits'],
						'board' => array(
							'id' => $row['id_board'],
							'name' => $row['bname'],
							'href' => $scripturl . '?board=' . $row['id_board'],
							'link' => '<a href="' . $scripturl . '?board=' . $row['id_board'] . '">' . $row['bname'] . '</a>'
						)
					);
					determineTopicClass($context['topics'][$row['id_topic']]);
				}
				// tidy up
				$smcFunc['db_free_result']($query);
				
				// show me which topics i've posted in
				if (!empty($modSettings['enableParticipation']) && !empty($topic_ids))
				{
					$query = $smcFunc['db_query']('', '
						SELECT id_topic
						FROM {db_prefix}messages
						WHERE id_topic IN ({array_int:topic_list})
							AND id_member = {int:user_id}',
						array(
							'topic_list' => $topic_ids,
							'user_id' => $user_info['id'],
						)
					);
					while ($row = $smcFunc['db_fetch_assoc']($query))
					{
						if (empty($context['topics'][$row['id_topic']]['is_posted_in'])) {
							$context['topics'][$row['id_topic']]['is_posted_in'] = true;
							$context['topics'][$row['id_topic']]['class'] = 'my_' . $context['topics'][$row['id_topic']]['class'];
						}
					}
					// tidy up
					unset($row);
					$smcFunc['db_free_result']($query);
				}

				$context['topics_to_mark'] = implode('-', $topic_ids);
						
				// next, show the results of our search
				$context['sub_template'] = 'results';
			
				// if we don't reach this point, we haven't been successful, so it will fall back to the 'general googletagged' bit
				$done = true;
			}
		}
	}
	
	// either there wasnt a id_tag or there was an error
	if(!$done)
	{	
		// start off with our tags
		$context['page_title'] = $mbname . ' - ' . $txt['googletagged'];
		
		// prepare a tag cloud
		// now this is complex because the same tag may have been saved multiple times
		// deleted topics where tags still remain will be ignored.
		
		$request = $smcFunc['db_query']('', '
			SELECT COUNT(g.tag)
			FROM {db_prefix}googletagged AS g
			WHERE g.status != {int:zero}',
			array(
				'zero' => 0,
			)
		);
		list ($context['googletagged_total']) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);
		
		$modSettings['disableQueryCheck'] = true;
		$query = $smcFunc['db_query']('', '
			SELECT g.tag, g.id_tag, g.hits, g.status, g.id_topic, t.id_topic
			FROM {db_prefix}googletagged as g, {db_prefix}topics as t
			WHERE g.id_topic = t.id_topic
				AND g.status != {int:zero}
				AND RAND() < (SELECT(({int:limit} / COUNT(*)) * 10) FROM {db_prefix}googletagged)
			GROUP BY g.tag
			ORDER BY RAND()
			LIMIT {int:limit}',
			array(
				'zero' => 0,
				'limit' => $modSettings['googletagged_limit_max'],
			)
		);
		$modSettings['disableQueryCheck'] = false;
		// found some tags?
		if($smcFunc['db_num_rows']($query) != 0)
		{
			// create an array for tags
			$context['googletagged'] = array();
			// cycle through
			$highest = 1 ;
			$lowest = 999999999999 ; // SO IT FORCES THE FIRST ROW TO BE THE LOWEST
			while($row = $smcFunc['db_fetch_assoc']($query)) {
				// STORE THE INFO FOR LATER ON
				$context['googletagged'][] = $row;
				$highest = ($row['hits'] > $highest) ? $row['hits'] : $highest ;
				$lowest = ($row['hits'] < $lowest) ? $row['hits'] : $lowest ;
			}
			// tidy up
			unset($row);
			// work out the sizes for us
			// first the max and min sizes that we can use in %
			$maxsize = 200;
			$minsize = 100;
			// whats the difference - if 0, dividing my zero will cause an error
			$diff = ($highest - $lowest == 0) ? 1 : ($highest - $lowest) ;
			// evenly step the tags
			$steps = ($maxsize - $minsize)/$diff;
			
			// cycle through our tags
			foreach ($context['googletagged'] as $key => $row) {
				// ADD THE COLUMN FOR SIZE
				
				$context['googletagged'][$key]['size'] = ceil($minsize + ($steps * ($row['hits'] - $lowest)));
				$context['googletagged'][$key]['text'] = str_replace("+", " ", $context['googletagged'][$key]['tag']);
			}
			// tidy up
			unset($key,$row,$steps,$highest,$lowest,$maxsize,$minsize);
			$smcFunc['db_free_result']($query);
			
			// second point - if not reached the end by here - error!!!
			$done = true;
		}
	}
	
	// send an error
	if(!$done){
		$context['googletagged_errors'] = true;
	}
	
}

// ban tag - will remove all but one instance of the tag throughout, and set its status to 0, so it will never be shown again.
function BanTag($tag)
{
	global $txt, $smcFunc, $modSettings;
	
	// check permissions
	isAllowedTo('googletagged_manage');
	
	// make lowercase
	$tag = strtolower($tag);
	$tag = preg_match('~^[-0-9a-z]{'.$modSettings['googletagged_min_length'].','.$modSettings['googletagged_max_length'].'}$~', $tag) ? $tag : '' ;
	if(!empty($tag))
	{
		// GET TAG
		$query = $smcFunc['db_query']('', '
			SELECT id_tag, tag
			FROM {db_prefix}googletagged
			WHERE tag = {string:tag}
			LIMIT 1',
			array(
				'tag' => $tag,
			)
		);
	
		// found tag
		if ($smcFunc['db_num_rows']($query) != 0)
		{
			// get the tag	
			$row = $smcFunc['db_fetch_assoc']($query);
			$idtag = $row['id_tag'];
			// tidy up
			unset($row);
			$smcFunc['db_free_result']($query);
		
			// ban that occurance by setting status = 0 & topic_id = 0 (then it can't be re-added to any topic)
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}googletagged
				SET status = {int:status}, id_topic = {int:id_topic}
				WHERE id_tag = {int:id_tag}
				LIMIT 1',
				array(
					'status' => 0,
					'id_topic' => 0,
					'id_tag' => $idtag
				)
			);
			
			// delete all the remaining occurances
			$smcFunc['db_query']('', '
				DELETE FROM {db_prefix}googletagged
				WHERE tag = {string:tag}
					AND id_tag != {int:id_tag}
					AND id_topic != {int:id_topic}',
				array(
					'tag' => $tag,
					'id_tag' => $idtag,
					'id_topic' => 0,
				)
			);
		}
		else
		{
			// tag currently doesnt exist, so insert it
			$smcFunc['db_insert']('normal', '{db_prefix}googletagged',
				array(
					'id_topic' => 'int', 'tag' => 'string-65534', 'hits' => 'int', 'status' => 'int',
				),
				array(
					0, $tag, 1, 0,
				),
				array('id_tag')
			);
		}
	}	
}

// unban tag
function UnBanTag()
{
	global $txt, $smcFunc;
	
	// check permissions
	isAllowedTo('googletagged_manage');
	
	// make lowercase
	$_REQUEST['id'] = (int) $_REQUEST['id'];
	
	// delete all the remaining occurances
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}googletagged
		WHERE id_tag = {int:id_tag}',
		array(
			'id_tag' => $_REQUEST['id'],
		)
	);
}

// reset tags (basically all hits are set to 0)
function ResetTags()
{
	global $txt, $smcFunc;
	
	// check permissions
	isAllowedTo('googletagged_manage');
	
	// resets all hits to 0
	$smcFunc['db_query']('', '
		UPDATE {db_prefix}googletagged
		SET hits = {int:zero}',
		array(
			'zero' => 0,
		)
	);
}

// optimize tags (checks for tags where topics were deleted, banned tags where possibly ban didnt work properly)
function OptimizeTags()
{
	global $txt, $smcFunc, $context;
	
	// check permissions
	isAllowedTo('googletagged_manage');
	
	// no banned words?
	if(isset($context['googletagged_banned'])) {
		foreach($context['googletagged_banned'] as $id_tag => $tag) {
			// delete all the remaining occurances
			$smcFunc['db_query']('', '
				DELETE FROM {db_prefix}googletagged
				WHERE tag = {string:tag}
					AND id_tag != {int:id_tag}',
				array(
					'tag' => $tag,
					'id_tag' => $id_tag,
				)
			);
		}
	}
	
	// load all tags for each topic, grouped by topic
	$query = $smcFunc['db_query']('', '
		SELECT id_topic, id_tag, tag
		FROM {db_prefix}googletagged
		WHERE id_topic != {int:zero}
		GROUP BY id_topic',
		array(
			'zero' => 0,
		)
	);		
	if($smcFunc['db_num_rows']($query) != 0)
	{
		// put all the topic ids in an array
		$topic_ids = array();
		while($row = $smcFunc['db_fetch_assoc']($query))
				$topic_ids[] = $row['id_topic'];
		// tidy up
		$smcFunc['db_free_result']($query);
		unset($row);
		
		// cycle through the topic ids, checking that they exist
		foreach($topic_ids as $topic_id)
		{	
			$query = $smcFunc['db_query']('', '
				SELECT id_topic
				FROM {db_prefix}topics
				WHERE id_topic =  {int:id_topic}',
				array(
					'id_topic' => $topic_id,
				)
			);
			// if no topic found with that id
			if($smcFunc['db_num_rows']($query) == 0)
			{
				// so delete all tags relating to that topic
				$smcFunc['db_query']('', '
					DELETE FROM {db_prefix}googletagged
					WHERE id_topic = {int:id_topic}',
					array(
						'id_topic' => $topic_id,
					)
				);
			}
			// tidy up
			$smcFunc['db_free_result']($query);
		}
		// tidy up
		unset($topic_ids,$topic_id);
	}
}

// the admin panel for banning tags and such
function ShowGoogleTaggedAdmin()
{
	global $smcFunc, $context, $txt, $modSettings, $settings;
	
	// check permission to manage
	isAllowedTo('googletagged_manage');
	
	loadTemplate('GoogleTagged');
	
	// unban tag?
	if(isset($_REQUEST['unban'])) {
		UnBanTag();
		// redirect back to the admin
		redirectexit('action=admin;area=modsettings;sa=googletagged;' . $context['session_var'] . '=' . $context['session_id']);
	}
	// reset tags? to 0 hits?
	if(isset($_REQUEST['reset'])) {
		ResetTags();
		// REDIRECT BACK TO THE ADMIN
		redirectexit('action=admin;area=modsettings;sa=googletagged;' . $context['session_var'] . '=' . $context['session_id']);
	}
	// save googletagged setting
	if(isset($_POST['save']))
	{
		$googletagged = isset($_POST['googletagged']) ? 1 : 0 ;
		$together = isset($_POST['googletagged_together']) ? 1 : 0 ;
		$maxlen = (int) $_POST['googletagged_max_length'];
		$minlen = (int) $_POST['googletagged_min_length'];
		$limitmax = (int) $_POST['googletagged_limit_max'];
		$limitmaxd = (int) $_POST['googletagged_limit_max_display'];
	
		// save the settings
		updateSettings(
			array(
				'googletagged' => $googletagged,
				'googletagged_together' => $together,
				'googletagged_max_length' => $maxlen,
				'googletagged_min_length' => $minlen,
				'googletagged_limit_max' => $limitmax,
				'googletagged_limit_max_display' => $limitmaxd,
			)
		);
		
		// ban each word in the ban tag fields-
		if(!empty($_POST['googletagged_addwordban'])) {
			foreach($_POST['googletagged_addwordban'] as $banned_tag) {
				BanTag($banned_tag);
			}
		}
		
		// as have saved - redirect to avoid resending data warnings
		redirectexit('action=admin;area=modsettings;sa=googletagged;' . $context['session_var'] . '=' . $context['session_id']);
	}
	
	// load all the banned tags/words
	$query = $smcFunc['db_query']('', '
		SELECT id_tag, tag, status
		FROM {db_prefix}googletagged
		WHERE status = {int:zero}',
		array(
			'zero' => 0,
		)
	);
	
	// if we found some tags, put them in an array
	if ($smcFunc['db_num_rows']($query) != 0)
	{
		$context['googletagged_banned'] = array();
		while($row = $smcFunc['db_fetch_assoc']($query))
			$context['googletagged_banned'][$row['id_tag']] = $row['tag'];
	}
	//optimize tags (checks for tags without topics, or banned ones still in)
	if(isset($_REQUEST['optimize']))
	{
		OptimizeTags();
		// redirect back to the admin
		redirectexit('action=admin;area=modsettings;sa=googletagged;' . $context['session_var'] . '=' . $context['session_id']);
	}		
	//	load the template
	$context['page_title'] = $txt['googletagged_admin'];
	$context['sub_template'] = 'ShowGoogleTaggedAdmin';
	$context['html_headers'] .= '
	<link rel="stylesheet" type="text/css" href="' . $settings['default_theme_url'] . '/css/gt.css" />';
}

function DisplayGoogleTagged()
{
	global $smcFunc, $context, $modSettings, $topic, $sourcedir;
	
	$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
	// if wanting to test with various serious urls, uncomment the next line
	// $referer = "http://www.google.com/search?hl=en&q=harry_potter&meta=";
	
	if(!empty($referer))
	{
		// detect certain search engines
		if(preg_match('/www\.google.*/i',$referer)
			|| preg_match('/search\.msn.*/i',$referer)
			|| preg_match('/search\.yahoo.*/i',$referer)
			|| preg_match('/search\.lycos\.com/i', $referer)
			|| preg_match('/www\.alltheweb\.com/i', $referer)
			|| preg_match('/search\.aol\.com/i', $referer)
			|| preg_match('/www\.ask\.*/i', $referer)) {

			// detected a search engine referred - now get the search term delimiter
			//Figure out which search and get the part of its URL which contains the search terms.
			if(preg_match('/(www\.google.*)|(search\.msn.*)|(www\.alltheweb\.com)|(ask\.*)/i',$referer))
				$delimiter = "q";
			elseif(preg_match('/(search\.lycos\.com)|(search\.aol\.com)/i', $referer))
				$delimiter = "query";
			elseif(preg_match('/search\.yahoo.*/i',$referer))
				$delimiter = "p";

			// now use the delimiter
			$pattern = "/^.*" . $delimiter . "=([^&]+)&?.*\$/i";
			$query = preg_replace($pattern, '$1', $referer);
			// TIDY UP
			unset($pattern,$delimiter);
			
			// remove our quotes
			$query = preg_replace('/\'|"/','',$query);

			// common ignore/bad words/unwanted bits
			$ignorewords = array(
					'a', 'am', 'an', 'and', 'are', 'be', 'been', 'because', 'by',
					'for', 'from', 'he', 'her', 'his', 'i', 'in', 'is', 'isnt', 'its', 'it',
					'of', 'on', 'or', 'over', 'off', 'that', 'the', 'there', 'their', 'this', 'was', 'with',
					'fuck', 'dick', 'wanker', 'bastard', 'fucking', 'whore', 'cunt', 'bitch', 'fucker',
					'http://www', 'www', 'www.', 'http://', 'com', 'co.uk', 'v1agra', 'viagra',
					'<script', 'javascript'
			);
			$search_terms = preg_split ("/[\s,\+\.]+/",$query);
		
			// re-case each search term and remove ignore words
			foreach($search_terms as $key => $term)
			{
				
				// LOWERCASE
				$term = strtolower($term);
				$term = preg_match('/^[-0-9a-z]{'.$modSettings['googletagged_min_length'].','.$modSettings['googletagged_max_length'].'}$/', $term) ? $term : '' ;
				
				// set some min/max lengths
				if(strlen($term) < $modSettings['googletagged_min_length'] || strlen($term) > $modSettings['googletagged_max_length'])
					unset($search_terms[$key]);
					
				// if ignore/bad word, then remove it
				if(in_array($term, $ignorewords))
					unset($search_terms[$key]);
					
				// replaces any spaces with plus
				$search_terms[$key] = str_replace(" ", "+", $term);
			}
			// tidy up
			unset($key,$term);
			
			// implode our array back to a string
			$search_terms = implode("+", $search_terms);
			$tags = htmlspecialchars(urldecode($search_terms));
		}
	}	
	// 	now check / store / update the tags
	if(!empty($tags) && !empty($topic)) {
		
		// if any remaining spaces got past us, double check
		$tags = str_replace(" ", "+", $tags);
		
		// if multiple tags do we store the tags these together or separately?
		if(!empty($modSettings['googletagged_together'])) {
			// then tags will be added together
			$tags = array($tags);
		} else {
			// do each tag individually
			$tags = strpos($tags, '+') ? explode('+', $tags) : array($tags);
		}
		
		// foreach tag check and insert/update as necessary
		// whether(together or individually depends on the above setting)
		foreach($tags as $tag) {
			// check tag is not banned
			$query = $smcFunc['db_query']('', '
				SELECT id_tag
				FROM {db_prefix}googletagged
				WHERE tag = {string:tag}
					AND status = {int:zero}
					AND id_topic = {int:zero}
				LIMIT 1',
				array(
					'tag' => $tag,
					'zero' => 0,
				)
			);
			// if not banned
			if($smcFunc['db_num_rows']($query) == 0) {
				$smcFunc['db_free_result']($query);
				// check whether tagged before - if so increase hits
				$query = $smcFunc['db_query']('', '
					SELECT id_tag
					FROM {db_prefix}googletagged
					WHERE id_topic = {int:id_topic} AND tag = {string:tag}
					LIMIT 1',
					array(
						'id_topic' => $topic,
						'tag' => $tag,
					)
				);
				// exists so just update hits
				if($smcFunc['db_num_rows']($query) == 1) {
					list($idtag) = $smcFunc['db_fetch_row']($query);
					// update hits + 1
					$smcFunc['db_query']('', '
						UPDATE {db_prefix}googletagged
						SET hits = hits + 1
						WHERE id_tag = {int:id_tag}
						LIMIT 1',
						array(
							'id_tag' => $idtag,
						)
					);
					// tidy up
					unset($idtag);
					$smcFunc['db_free_result']($query);
				}
				else
				{
					$smcFunc['db_insert']('normal', '{db_prefix}googletagged',
						array( 'id_topic' => 'int', 'tag' => 'string-65534', 'hits' => 'int', 'status' => 'int', ),
						array( $topic, $tag, 1, 1, ),
						array('id_tag')
					);		
				}
			}
		}
		// tidy up
		unset($tag);
	}
	// now prepare the tags for this topic
	if(!empty($topic)) {
		$query = $smcFunc['db_query']('', '
			SELECT id_tag, status, hits, tag
			FROM {db_prefix}googletagged
			WHERE id_topic = {int:id_topic} AND status != {int:status}
			ORDER BY hits DESC
			LIMIT {int:limit}',
			array(
				'id_topic' => $topic,
				'status' => 0,
				'limit' => $modSettings['googletagged_limit_max_display'],
			)
		);
		if($smcFunc['db_num_rows']($query) != 0) {
			$context['tags'] = array();
			$highest = 1 ;
			$lowest = 999999999999 ; // SO IT FORCES THE FIRST ROW TO BE THE LOWEST
			while($row = $smcFunc['db_fetch_assoc']($query)) {
				// store the info for later on
				$context['tags'][$row['id_tag']] = $row;
				$highest = ($row['hits'] > $highest) ? $row['hits'] : $highest ;
				$lowest = ($row['hits'] < $lowest) ? $row['hits'] : $lowest ;
			}
			// tidy up
			unset($row);
			// work out the sizes for us
			// first the max and min sizes that we can use in %
			$maxsize = 200;
			$minsize = 100;
			// whats the difference - if 0, dividing my zero will cause an error
			$diff = ($highest - $lowest == 0) ? 1 : ($highest - $lowest) ;
			// evenly step the tags
			$steps = ($maxsize - $minsize)/$diff;
		
			// cycle through our tags
			foreach ($context['tags'] as $key => $row) 
			{
				// add the column for size
				$context['tags'][$key]['size'] = ceil($minsize + ($steps * ($row['hits'] - $lowest)));
				// did this visitor just come from google and this tag was google tagged
				// don't want + signs to join the words, so replace with space
				$context['tags'][$key]['text'] = str_replace("+", " ", $row['tag']);
				$context['tags'][$key]['tagged'] = (!empty($tags) && in_array($row['tag'],$tags)) ? true : false ;

			}
			// tidy up
			unset($key,$row,$steps,$highest,$lowest,$maxsize,$minsize);
			$smcFunc['db_free_result']($query);
		}
	}
}

// [n3rve] Kills it here: End of Google Tagged mod

?>