<?php 
header('Content-Type: text/xml; charset=utf-8');

/**
 * iTunes-Compatible RSS 2.0 MP3 subscription feed script
 * Original work by Rob W of http://www.podcast411.com/
 * Updated by Aaron Snoswell (aaronsnoswell@gmail.com)
 * Further updated by Wesley Teal (wesley.teal@wcfcourier.com)
 *
 * Recurses a given directory, reading MP3 ID3 tags and generating an itunes
 * compatible RSS podcast feed.
 *
 * Save this .php file wherever you like on your server. The URL for this .php
 * file /is/ the URL of your podcast feed for subscription purposes.
 */

/*
 * CONFIGURATION VARIABLES:
 * For more info on these settings, see the instructions at
 *
 * http://www.apple.com/itunes/podcasts/specs.html
 *
 * and the RSS 2.0 spec at
 *
 * http://www.rssboard.org/rss-specification
 */


// ============================================ General Configuration Options

// Location of MP3's on server. 
$files_dir = $_SERVER['DOCUMENT_ROOT'] . "/pat/to/your/files/";

// Corresponding url for accessing the above directory. 
$files_url = "http://yoursite.com/path/to/your/files/";

// Location of getid3 folder, leave blank to disable. 
$getid3_dir = $_SERVER['DOCUMENT_ROOT'] . "/path/to/getid3/";

// ====================================================== Generic feed options

// Your feed's title
$feed_title = "My podcast";

// 'More info' link for your feed
$feed_link = "http://yoursite.com/path/to/your/files/feed.php";

// Brief description
$feed_description = "I made this.";

// Copyright / license information
$feed_copyright = "All content &#0169; Jane Doe " . date("Y");

// How often feed readers check for new material (in seconds) -- mostly ignored by readers
$feed_ttl = 60 * 60 * 24 * 7;

// Language locale of your feed, eg. en-us, de, fr etc. See http://www.rssboard.org/rss-language-codes
$feed_lang = "en-us";

// File extension for episodes, eg. ".mp3" or ".mp4"
$file_ext = ".mp3";

// MIME type for episodes, eg. "audio/mpeg" for mp3 files or ""video/mp4" for mp4 files
$mime_type = "audio/mpeg";


// ============================================== iTunes-specific feed options

// You, or your organisation's name
$feed_author = "Jane Doe";

// Feed author's contact email address
$feed_email="janedoe@yoursite.com";

// Url of a 170x170 .png image to be used on the iTunes page
$feed_image = "http://yoursite.com/path/to/logo/logo.jpg";

// If your feed contains explicit material or not (yes, no, clean)
$feed_explicit = "clean";

// iTunes major category of your feed (complete category/subcategory listing available at: https://www.apple.com/itunes/podcasts/specs.html)
$feed_category = "";

// iTunes minor category of your feed
$feed_subcategory = "";

// END OF CONFIGURATION VARIABLES

// If getid3 was requested, attempt to initialise the ID3 engine
$getid3_engine = NULL;
if(strlen($getid3_dir) != 0) {
    require_once(rtrim($getid3_dir, '/') . '/' . 'getid3.php');
    $getid3_engine = new getID3;
}

// Write XML heading
echo '<?xml version="1.0" encoding="utf-8" ?>';

?>

<rss xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" version="2.0">

    <channel>
        <title><? echo $feed_title; ?></title>
        <link><? echo $feed_link; ?></link>

        <!-- iTunes-specific metadata -->
        <itunes:author><? echo $feed_author; ?></itunes:author>
        <itunes:owner>
            <itunes:name><? echo $feed_author; ?></itunes:name>
            <itunes:email><? echo $feed_email; ?></itunes:email>
        </itunes:owner>

        <itunes:image href="<? echo $feed_image; ?>" />
        <itunes:explicit><? echo $feed_explicit; ?></itunes:explicit>
        <itunes:category text="<? echo $feed_category; ?>">
            <itunes:category text="<? echo $feed_subcategory; ?>" />
        </itunes:category>

        <itunes:summary><? echo $feed_description; ?></itunes:summary>

        <!-- Non-iTunes metadata -->
        <category><? echo $feed_category; ?></category>
        <description><? echo $feed_description; ?></description>
        
        <language><? echo $feed_lang; ?></language>
        <copyright><? echo $feed_copyright; ?></copyright>
        <ttl><? echo $feed_ttl; ?></ttl>

        <!-- The file listings -->
        <?php
        $directory = opendir($files_dir) or die($php_errormsg);

        // Step through file directory
        while(false !== ($file = readdir($directory))) {
			if ($file != "." && $file != "..") {
				// Prepare files for sorting
				$files[filemtime($file)] = $file;
			}
		}
		closedir($files_dir);
		
		// Sort files in reverse chronological order
		krsort($files);
		
		// Create feed entries
		foreach($files as $file) {
            $file_path = rtrim($files_dir, '/') . '/' . $file;

            // not . or .., ends in $file_ext
            if(is_file($file_path) && strrchr($file_path, '.') == $file_ext) {
                // Initialise file details to sensible defaults
                $file_title = $file;
                $file_url = rtrim($files_url, '/') . '/' . $file;
                $file_author = $feed_author;
                $file_duration = "";
                $file_description = "";
                $file_date = date(DateTime::RFC2822, filemtime($file_path));
                $file_size = filesize($file_path);

                // Read file metadata from the ID3 tags
                if(!is_null($getid3_engine)) {
                    $id3_info = $getid3_engine->analyze($file_path);
                    getid3_lib::CopyTagsToComments($id3_info);
                    
                    $file_title = $id3_info["comments_html"]["title"][0];
                    $file_author = $id3_info["comments_html"]["artist"][0];
                    $file_duration = $id3_info["playtime_string"];
					$file_description = $id3_info["comments_html"]["comment"][0];
                }
?>

        <item>
            <title><? echo $file_title; ?></title>
            <link><? echo $file_url; ?></link>
            
            <itunes:author><? echo $file_author; ?></itunes:author>
            <itunes:category text="<? echo $feed_category; ?>">
                <itunes:category text="<? echo $feed_subcategory; ?>" />
            </itunes:category>

            <category><? echo $feed_category; ?></category>
            <duration><? echo $file_duration; ?></duration>
            
			<itunes:summary><? echo $file_description; ?></itunes:summary>
            <description><? echo $file_description; ?></description>
            <pubDate><? echo $file_date; ?></pubDate>

            <enclosure url="<? echo $file_url; ?>" length="<? echo $file_size; ?>" type="<? echo $mime_type; ?>" />
            <guid><? echo $file_url; ?></guid>
            <author><? echo $feed_email; ?></author>
        </item>
<?
		}
    }



?>

    </channel>
</rss>