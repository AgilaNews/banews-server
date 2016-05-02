import unittest
import requests
import config
import json
import random

COMMENT = "http://%s:%s/login" % (config.HOST, config.PORT)

class CommentTestCase(unittest.TestCase):
    def _getTestFixture(self):
        ret = [
            {
                "news_id": "o40oQGrbXOk=",
                "comment": "l love this game",
            }
        ]
        return ret

    def test_std(self):
        fixture = self._getTestFixture()[0]
        resp = requests.post(COMMENT, data = json.dumps(fixture))
        self.assertEqual(resp.status_code, 200)


    def test_comment_non_exists(self):
        fix = {
            "news_id": "nononon",
            "comment": "xxx",
        }
        self.assertNotEqual(resp.status_code, 200)
