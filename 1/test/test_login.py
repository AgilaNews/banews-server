import unittest
import requests
import config
import json
import random

LOGIN = "http://%s:%s/login/" % (config.HOST, config.PORT)

class LoginTestCase(unittest.TestCase):
    def _getNormalFixture(self):
        ret = []
        for src in ["facebook", "googleplus", "twitter"]:
            ret.append({"uid": "12345", 
                        "source": src, 
                        "name": "testcase", 
                        "gender": "1", 
                        "portrait": "http://doubi.com",
                        "email": "zgx@gmail.com"})
        return ret


    def test_std(self):
        for fixture in self._getNormalFixture():
            resp = requests.post(LOGIN, data = json.dumps(fixture))
            self.assertEqual(resp.status_code, 200)
            obj = json.loads(resp.text)
            self.assertIn("id", obj)
            self.assertIn("name", obj)
            self.assertIn("gender", obj)
            self.assertIn("portrait", obj)
            self.assertIn("email", obj)
            self.assertIn("create_time", obj)
            self.assertIn("source", obj)
            for key,v in fixture.iteritems():
                if key != "uid":
                    self.assertEqual(fixture[key], obj[key])
    
    def test_dup_uid(self):
        fs =  self._getNormalFixture()
        a = b = fs[0]

        resp1 = requests.post(LOGIN, data = json.dumps(a))
        resp2 = requests.post(LOGIN, data = json.dumps(b))
        self.assertEqual(resp1.status_code, 200)
        self.assertEqual(resp2.status_code, 200)
        self.assertEqual(resp1.text, resp2.text)
        

    def test_invalid_method(self):
        resp = requests.get(LOGIN)
        self.assertEqual(resp.status_code, 405)
        obj = json.loads(resp.text)
        self.assertIn("code", obj)
        self.assertEqual(obj["code"], 40501)
