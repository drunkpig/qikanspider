# -*- coding: utf-8 -*-

__author__ = 'cxu'
import json
import redis
import hashlib
import fileinput

file_list = {
    "11185" : "../11185/11185_detail.log",
    "cnki" : "../cnki/cnki_detail.log",
    "cqvip" : "../cqvip/cqvip_detail.log",
    "wanfang" : "../wanfang/wanfang_detail.log",
    "emuch_zh" : "../emuch/emuch_detail_zh.log",
    "emuch_en" : "../emuch/emuch_detail_en.log"
    }

all_keys = {}  #  存放全部的由get_key生成的key,最后阶段利用这些key，从缓存中取出最终的数据

redis_client = redis.StrictRedis(host='localhost', port=6379, db=0)

def get_format_book_name(book_name):
    """
    去除书名字里带括号的备注
    :param book_name:
    :return:
    """
    formated_bk_name = book_name
    if book_name is not None:
        i = book_name.find("(")
        if i<=0:
            i = book_name.find("（")
        if i>0:
            formated_bk_name = book_name[0:i].strip()
    return formated_bk_name

def get_key(book_zh, book_en):
    """
    使用去括号的中文和英文书名生成去重用的key
    :param book_zh:
    :param book_en:
    :return:
    """
    zh_key = get_format_book_name(book_zh)
    en_key = get_format_book_name(book_en)
    key = zh_key + en_key
    return hashlib.md5(key.encode("utf-8"))

def merge(map1, map2):
    """
    合并两个刊物的信息
    :param map1:
    :param map2:
    :return:
    """
    result = map1.copy();
    for key in map2:
        val1_len = len(result[key])
        val2_len = len(map2[key])
        if(val2_len>val1_len):  #  取数据量大的
            result[key] = map2[key]

    return result

def get_cache(key):
    """
    从缓存读出key
    :param key:
    :return:
    """
    map = None
    json_str = redis_client.get(key)
    if json_str is not None:
        map = json.load(json_str)

    return map;

def put_cache(key, map):
    """
    把map放入到cache
    :param map:
    :return:
    """
    all_keys[key] = 1;
    json_map = json.dumps(map)
    redis_client.set(key, json_map)

def log_key(key):
    with open("./keys.log", "w+") as f:
        f.write(key+"\n");

def process_a_file(file):
    """
    处理一个文件，利用redis合并数据
    :param file:
    :return:
    """
    with fileinput.input(file) as f:
        for line in f:
            map = json.loads(line)
            book_zh = map['book_name_zh']
            book_en = map['book_name_en']
            redis_key = get_key(book_zh, book_en)
            map2 = get_cache(redis_key)
            if map2 is not None:
                allmap = merge(map, map2)
                put_cache(redis_key, allmap)
            else:
                put_cache(redis_key, map)

            log_key(redis_key)


for key in file_list:
    val = file_list[key]
    print("begin to process file : %s => %s" % (key, val), end="\n")
    process_a_file(val)
    print("end process file %s", (val), end="\n");

if __name__ == "__main__":
    print(get_format_book_name("中国人 |(china people)"), end="\n")
    print(get_format_book_name("国外 |（foreign）"), end="\n")

