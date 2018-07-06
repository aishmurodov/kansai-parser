<?

	include 'Db.php';

	$db = new Db();

	$all_pages = 18;
	$perpage = 10;

	$all_cats_array = $db->row("SELECT `tag` FROM `kansai_tags`");
	$all_cats = [];
	foreach ($all_cats_array as $cat) {
		$all_cats[] = $cat['tag'];
	}

	for ($i = 1; $i <= $all_pages ; $i++) {
		$postsArray = json_decode(file_get_contents("https://kansai.studio/api/getpage/$i/$perpage"), true);

		$posts = $postsArray["posts"];
		foreach ($posts as $post) {
			$additional = [
				[
					"key" => "Год выпуска",
					"value" => $post['year'],
					"ishidden" => "on"
				]
			];
			$params = [
				"title" => $post['name'],
				"original_title" => $post['original'],
				"totalseries" => $post['totalseries'],
				"torrent" => json_encode($post['torrent']),
				"poster" => $post['imageurl'],
				"description" => $post['description'],
				"views" => $post['views'],
				"url" => $post['url'],
				"additional" => json_encode($additional),
			];

			$categories = $post['category'];
			$cats_ids = [];
			foreach ($categories as $categorie) {
				if (!in_array($categorie, $all_cats)) {
					$all_cats[] = $categorie;
					$db->query("INSERT INTO `kansai_tags` (`tag`) VALUES('$categorie')");
				}
			}

			$db->query("INSERT INTO `kansai_posts` (`title`,`original_title`,`totalseries`,`torrent`,`poster`,`description`,`views`,`url`,`additional`) VALUES(:title,:original_title,:totalseries,:torrent,:poster,:description,:views,:url,:additional)", $params);

			$post_id = $db->lastInsertId();

			foreach ($categories as $categorie) {
				$cat_id = $db->column("SELECT `id` FROM `kansai_tags` WHERE `tag` = '$categorie'")[0];
				$db->query("INSERT INTO `kansai_posts_tags` (`postid`, `tagid`) VALUES($post_id,$cat_id)");
			}

			$series_array = file_get_contents("https://kansai.studio/api/getPost/".$post['url']);
			$series = json_decode($series_array, true)['series'];

			foreach ($series as $serie) {
				$params = [
					"postid" => $post_id,
					"url" => $serie['links'][720],
					"number" => $serie['number']
				];
				$db->query("INSERT INTO `kansai_posts_series` (`postid`, `url`, `number`) VALUES(:postid,:url,:number)", $params);
			}
		}
	}
