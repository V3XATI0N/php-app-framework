<?php
startSession();
clearUserActions($_SESSION['id']);
session_destroy();
returnToHome();