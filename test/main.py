#!/usr/bin/env python
#-*- coding:utf-8 -*-
import os
import unittest

if __name__ == "__main__":
    loader = unittest.TestLoader()
    suite = loader.discover(".")
    unittest.main(verbosity = 2)
