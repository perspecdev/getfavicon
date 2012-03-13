<?php
$url = $_GET['url'];
$preferred_size = max(0, clean_number($_GET['preferred_size']));

$root_url = get_root_url($url);

if (!empty($root_url)) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_TIMEOUT, 30);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
  $html = curl_exec($ch);
  $curl_info = curl_getinfo($ch);
  curl_close($ch);
  
  $root_url = get_root_url($curl_info['url']);
  
  $links = array();
  if (preg_match_all('/<link[^>]+?rel="?(?:icon|shortcut\sicon)"?.*?>/is', $html, $link_matches)) {
    foreach ($link_matches[0] as $link) {
      $this_link = array();
      $this_link['href'] = '';
      $this_link['sizes'] = array();
      if (preg_match('/href="?(.+?)["\s]/is', $link, $href_matches)) {
        $this_link['href'] = $href_matches[1];
      }
      if (preg_match('/sizes="?(.+?)["\s]/is', $link, $sizes_matches)) {
        $sizes = explode(' ', $sizes_matches[1]);
        foreach ($sizes as $size) {
          if (empty($size)) continue;
          $width_height = explode('x', $size);
          if (count($width_height) == 2) {
            $this_size = array();
            $this_size['width'] = $width_height[0];
            $this_size['height'] = $width_height[1];
            
            $this_link['sizes'][] = $this_size;
          }
        }
      }
      
      $links[] = $this_link;
    }
    
    $favicon = '';
    $last_width = 0;
    foreach ($links as $link) {
      foreach ($link['sizes'] as $size) {
        if ($size['width'] == $preferred_size && $size['height'] == $preferred_size) {
          $favicon = $link['href'];
          break 2;
        }
        
        if ($size['width'] == $preferred_size) {
          $this_width = (int)$size['width'];
          if (abs($last_width, $this_width) < abs($preferred_width, $this_width)) continue;
          
          $favicon = $link['href'];
          $last_width = $this_width;
        }
      }
    }
    
    if (empty($favicon)) $favicon = $links[0]['href'];
  } else {
    $found_favicon = false;
    if (!$found_favicon && checkURL($root_url . 'favicon.ico')) {
      $favicon = 'favicon.ico';
      $found_favicon = true;
    }
    if (!$found_favicon && checkURL($root_url . 'favicon.png')) {
      $favicon = 'favicon.png';
      $found_favicon = true;
    }
    if (!$found_favicon && checkURL($root_url . 'favicon.gif')) {
      $favicon = 'favicon.gif';
      $found_favicon = true;
    }
    if (!$found_favicon && checkURL($root_url . 'favicon')) {
      $favicon = 'favicon';
      $found_favicon = true;
    }
  }
  
  if (!empty($favicon)) {
    $favicon_parts = parse_url($favicon);
    if (!empty($favicon_parts['host'])) {
      echo $favicon;
      die();
    } else {
      $favicon = preg_replace('/^\//', '', $favicon);
      echo $root_url . $favicon;
      die();
    }
  }
}

function get_root_url($url) {
  $root_url = '';
  
  $url_parts = parse_url($url);
  if (!empty($url_parts)) {
    if (empty($url_parts['scheme'])) $url_parts['scheme'] = 'http';
    
    $root_url  = '';
    $root_url .= $url_parts['scheme'] . '://';
    $root_url .= $url_parts['user'];
    if (!empty($url_parts['pass'])) $root_url .= ':' . $url_parts['pass'];
    if (!empty($url_parts['user'])) $root_url .= '@';
    $root_url .= $url_parts['host'] . '/';
  }
  
  return $root_url;
}

function clean_number($number) {
  if (is_numeric($number)) {
    return $number;
  } else {
    return -1;
  }
}

function checkURL($url) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_HEADER, 1);
  curl_setopt($ch, CURLOPT_NOBODY, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_NOBODY, 1);
  curl_setopt($ch, CURLOPT_TIMEOUT, 2);
  curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
  if (!curl_exec($ch)) { return false; }

  $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  
  return ($http_code == 200);
}
?>