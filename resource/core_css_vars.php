<?php
include(__DIR__ . '/theme_colors.php');
?>
:root {
    --themeColor: <?= $themeColor ?>;
    --textOnTheme: <?= $textOnTheme ?>;
    --themeGrey: <?= $themeGrey ?>;
    --textOnThemeGrey: <?= $textOnThemeGrey ?>;
    --bodyBgColor: <?= $bodyBgColor ?>;
    --textOnBodyBgColor: <?= $textOnBodyBgColor ?>;
    --itemBgColor: <?= $itemBgColor ?>;
    --textOnItemBgColor: <?= $textOnBodyBgColor ?>;
    --sectionTriggerText: <?= $sectionTriggerText ?>;
    --sectionTriggerTextHover: <?= $sectionTriggerTextHover ?>;
    --objectHover: <?= $objectHover ?>;
    --textOnObjectHover: <?= $textOnObjectHover ?>;
    --imageBgColor: <?= $imageBgColor ?>;
    --menuActiveBg: <?= $menuActiveBg ?>;
    --textOnMenuActiveBg: <?= $textOnMenuActiveBg ?>;
    --navIconBg: <?= $navIconBg ?>;
    --themeLighter: <?= $themeLighter ?>;
    --textOnThemeLighter: <?= $textOnThemeLighter ?>;
    --themeLighter2: <?= $themeLighter2 ?>;
    --textOnThemeLighter2: <?= $textOnThemeLighter2 ?>;
    --themeDarker: <?= $themeDarker ?>;
    --textOnThemeDarker: <?= $textOnThemeDarker ?>;
    --themeDarker2: <?= $themeDarker2 ?>;
    --textOnThemeDarker2: <?= $textOnThemeDarker2 ?>;
    --greyLighter: <?= $greyLighter ?>;
    --textOnGreyLighter: <?= $textOnGreyLighter ?>;
    --greyLighter2: <?= $greyLighter2 ?>;
    --textOnGreyLighter2: <?= $textOnGreyLighter2 ?>;
    --themeColorText: <?= $themeColorText ?>;
    --default_monospace_font: 'Fira Mono', monospace;
    --default_variable_font: 'Fira Sans', sans-serif;
    --loading_svg: url("data:image/svg+xml,%3Csvg width='200px' height='200px' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100' preserveAspectRatio='xMidYMid' class='lds-rolling' style=''%3E%3Ccircle cx='50' cy='50' fill='none' ng-attr-stroke='%7B%7Bconfig.color%7D%7D' ng-attr-stroke-width='%7B%7Bconfig.width%7D%7D' ng-attr-r='%7B%7Bconfig.radius%7D%7D' ng-attr-stroke-dasharray='%7B%7Bconfig.dasharray%7D%7D' stroke='<?= $themeColorEncoded ?>' stroke-width='10' r='35' stroke-dasharray='164.93361431346415 56.97787143782138' transform='rotate(294 50 50)'%3E%3CanimateTransform attributeName='transform' type='rotate' calcMode='linear' values='0 50 50;360 50 50' keyTimes='0;1' dur='1s' begin='0s' repeatCount='indefinite'%3E%3C/animateTransform%3E%3C/circle%3E%3C/svg%3E");
}