__author__ = 'cxu'
import json
import redis


file_list = {
    "11185" : "../11185/11185_detail.log",
    "cnki" : "../cnki/cnki_detail.log",
    "cqvip" : "../cqvip/cqvip_detail.log",
    "wanfang" : "../wanfang/wanfang_detail.log",
    "emuch_zh" : "../emuch/emuch_detail_zh.log",
    "emuch_en" : "../emuch/emuch_detail_en.log"
    }

all_keys = {} #  存放全部的由get_key生成的key,最后阶段利用这些key，从缓存中取出最终的数据

def get_key(book_zh, book_en):
    """
    使用去括号的中文和英文书名生成去重用的key
    :param book_zh:
    :param book_en:
    :return:
    """
    pass

def merge(map1, map2):
    """
    合并两个刊物的信息
    :param map1:
    :param map2:
    :return:
    """
    pass

def get_cache(key):
    """
    从缓存读出key
    :param key:
    :return:
    """
    pass

def put_cache(key, map):
    """
    把map放入到cache
    :param map:
    :return:
    """
    all_keys[key] = 1;
    json_map = json.dumps(map)
    r = redis.StrictRedis(host='localhost', port=6379, db=0)
    r.set(key, json_map)

def process_a_file(file):
    """
    处理一个文件，利用redis合并数据
    :param file:
    :return:
    """

    pass


for key in file_list:
    val = file_list[key]
    print("begin to process file : %s => %s" % (key, val), end="\n")
    process_a_file(val)
    print("end process file %s", (val), end="\n");
