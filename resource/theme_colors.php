<?php

// profile options
global $url_query;
if (isset($_SESSION['userdata']) and isset($_SESSION['userdata']['theme']) and (!isset($url_query['theme_preview']) or $url_query['theme_preview'] != "true")) {
    if (isset($oset['user_themes']) and $oset['user_themes'] === true) {
        foreach ($_SESSION['userdata']['theme'] as $key => $val) {
            if (isset($oset_schema[$key]) and $oset_schema[$key]['group'] == "presentation") {
                $oset[$key] = $val;
            }
        }
    }
    if (isset($_SESSION['userdata']['theme']['local_dark_theme'])) {
        if ($_SESSION['userdata']['theme']['local_dark_theme'] === true) {
            if (isset($url_query['darktheme']) and $url_query['darktheme'] == "true") {
                $oset['dark_theme'] = true;
            }
        }
    }
}

// theme options
if (isset($oset['dark_theme']) and is_bool($oset['dark_theme'])) {
    $darkTheme = $oset['dark_theme'];
} else {
    $darkTheme = false;
}
/*
if (isset($url_query['darktheme']) and ($url_query['darktheme'] == "true" or $url_query['darktheme'] == "false")) {
    if ($url_query['darktheme'] == "false") {
        $darkTheme = false;
    } else {
        $darkTheme = true;
    }
}
*/


// base theme color
$themeColor = $oset['theme_color'];
$textOnTheme = textOnBgColor($themeColor);
$themeGrey = greyBright($themeColor, 0);
$textOnThemeGrey = textOnBgColor($themeGrey);

// grey + 96
$greyLighter = greyBright($themeColor, 96);
$textOnGreyLighter = textOnBgColor($greyLighter);

// grey + 128
$greyLighter2 = greyBright($themeColor, 128);
$textOnGreyLighter2 = textOnBgColor($greyLighter2);

// theme + 92
$themeLighter = adjustBrightness($themeColor, 92);
$textOnThemeLighter = textOnBgColor($themeLighter);

// theme + 116
$themeLighter2 = adjustBrightness($themeColor, 116);
$textOnThemeLighter2 = textOnBgColor($themeLighter2);

// grey - 8
$greyDarker = greyBright($themeColor, -8);
$textOnGreyDarker = textOnBgColor($greyDarker);

// grey - 14
$greyDarker2 = greyBright($themeColor, -14);
$textOnGreyDarker2 = textOnBgColor($greyDarker2);

// theme - 48
$themeDarker = adjustBrightness($themeColor, -48);
$textOnThemeDarker = textOnBgColor($themeDarker);

// theme - 64
$themeDarker2 = adjustBrightness($themeColor, -64);
$textOnThemeDarker2 = textOnBgColor($themeDarker2);

// specific items
$objectHover = $greyLighter;
$textOnObjectHover = textOnBgColor($objectHover);

// light/dark logic

if (isLightColor($themeColor)) {
    $imageBgColor = $themeDarker;
    $itemBgColor = setColorAlpha($greyDarker2, 0.6);
    $bodyBgColor = $greyDarker;
    $menuActiveBg = $themeDarker;
    $navIconBg = 'rgba(0, 0, 0, 0.15)';
} else {
    $imageBgColor = $themeColor;
    $itemBgColor = setColorAlpha($greyLighter2, 0.6);
    $bodyBgColor = $greyLighter;
    $menuActiveBg = $themeLighter;
    $navIconBg = 'rgba(255, 255, 255, 0.15)';
}

if ($darkTheme === true) {
    $bodyBgColor = adjustBrightness($greyDarker2, -36);
    $itemBgColor = setColorAlpha($greyDarker2, 0.6);
    $objectHover = $greyDarker2;
    $textOnObjectHover = $themeLighter;
    $textOnItemBgColor = $greyLighter2;
    $sectionTriggerText = $greyLighter;
    $sectionTriggerTextHover = $greyLighter2;
    $textOnBodyBgColor = $greyLighter2;
    $themeColorText = $themeLighter;
} else {
    $bodyBgColor = adjustBrightness($greyLighter2, 36);
    $itemBgColor = setColorAlpha(adjustBrightness($greyLighter2, -16), 0.6);
    $textOnBodyBgColor = $greyDarker2;
    $sectionTriggerText = $greyDarker2;
    $sectionTriggerTextHover = adjustBrightness($greyDarker2, -16);
    $textOnItemBgColor = $greyDarker2;
    $textOnObjectHover = $themeDarker;
    $themeColorText = $themeDarker;
}
$textOnMenuActiveBg = textOnBgColor($menuActiveBg);
$themeColorEncoded = urlencode($themeColor);
