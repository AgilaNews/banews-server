<?php
class UserSession {
    public static function genSessionId($client_token) {
        return md5($client_token . time());
    }
}
