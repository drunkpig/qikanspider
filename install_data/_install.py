# -*- coding: utf-8 -*-
from lib.imageb64 import image_b64_encode

__author__ = 'cxu'
import json
import redis
import hashlib
import fileinput
import os
from pymongo import MongoClient
from datetime import datetime

file_list = {
    "11185" : "../11185/11185_detail.log",
    "cnki" : "../cnki/cnki_detail.log",
    "cqvip" : "../cqvip/cqvip_detail.log",
    "wanfang" : "../wanfang/wf_detail.log",
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
    else:
        formated_bk_name = ""
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
    zh_key = zh_key.lower()
    en_key = en_key.lower()
    key = zh_key + en_key
    return hashlib.md5(key.encode("utf-8")).hexdigest()

def get_img_order(from_where):
    """
    cnki->wanfang->cqvip->11185
    :param from_where:
    :return:
    """
    order = {"11185" : 1, "cqvip" : 2, "wanfang":3, "cnki":4}
    i = order.get(from_where)
    if not i:
        i = 0;
    return 0

def merge(map1, map2):
    """
    合并两个刊物的信息
    :param map1:
    :param map2:
    :return:
    """
    result = map1.copy();
    for key in map2:
        val1_len = 0
        val1 = result.get(key)
        if val1 is not None:
            val1_len = len(str(val1))

        val2 = map2.get(key)
        val2_len = 0
        if val2 is not None:
            val2_len = len(str(val2))

        if val2_len>val1_len:  #取数据量大的
            result[key] = map2.get(key)

    #  特别处理yu_zhong , 去掉最后的#
    yu_zhong = result.get('yu_zhong')
    if yu_zhong is not None:
        len1 = len(yu_zhong)
        yu_zhong = yu_zhong[0:len1-1]
        result['yu_zhong'] = yu_zhong

    #  _from字段要合并起来
    from1 = map1['_from']
    from2 = map2['_from']
    result['_form'] = from1 + "#" + from2

    # 合并图片，图片优先级cnki->wanfang->cqvip->11185
    img1 = map1.get('feng_mian')
    img2 = map2.get('feng_mian')
    feng_mian = img1
    if img1 is not None and img2 is not None:
        ord1 = get_img_order(from1)
        ord2 = get_img_order(from2)
        if ord2 > ord1 > 0:
            feng_mian = img2
    elif img1 is not None:
        feng_mian = img1
    elif img2 is not None:
        feng_mian = img2
    else:
        feng_mian = ""

    result['feng_mian'] = feng_mian

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
        json_str = json_str.decode("utf-8")
        map = json.loads(json_str, encoding="utf-8")

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
        f.write(key+"\n")

def process_a_file(file):
    """
    处理一个文件，利用redis合并数据
    :param file:
    :return:
    """
    with fileinput.input(file) as f:
        f = open(file, errors="ignore")
        try:
            for line in f:
                if line is None:
                    continue
                line = line.strip()
                if len(line) <= 0:
                    continue
                try:
                    line = line.strip()
                    map = json.loads(line, encoding="utf-8")
                    #  print(str(map))
                    book_zh = map.get('book_name_zh')
                    book_en = map.get('book_name_en')
                    if book_zh is None and book_en is None:
                        continue
                    redis_key = get_key(book_zh, book_en)
                    map2 = get_cache(redis_key)
                    if map2 is not None:
                        allmap = merge(map, map2)
                        put_cache(redis_key, allmap)
                    else:
                        put_cache(redis_key, map)

                    log_key(redis_key)
                except ValueError as ve:
                    print("value error %s, %s\n", (line, ve))
        except UnicodeDecodeError as e:
            print("unicode error %s", line, end="\n")

for key in file_list:
    val = file_list[key]
    print("begin to process file : %s => %s" % (key, val), end="\n")
    process_a_file(val)
    print("end process file %s", (val), end="\n")

# 把redis里去重之后的结果放入到文件里
f = open("./temp", "w")
print("保存去重之后的内容到文件\n")
for key in all_keys:
    val = redis_client.get(key)
    if val is not None:
        string = val.decode("utf-8")
        string = json.loads(string, encoding="utf-8")
        string = json.dumps(string, ensure_ascii=False)
        f.write(string)
        f.write("\n")

print("保存文件成功，准备写入mongodb\n")

db = MongoClient(host="localhost", port=27017).db_qikan
collection = db.qikan_info
count = 0

with fileinput.input("./temp") as final_result_file:
    for line in final_result_file:
        string = line.strip()
        string = json.loads(string, encoding="utf-8")
        #  print(string, end="\n")
        string['gmt_create'] = datetime.now()
        #  图片编码为base64

        imageFile = string.get('feng_mian')
        if imageFile and len(imageFile)>0 and os.path.exists(imageFile):
            b64Img = image_b64_encode(imageFile)
            string['feng_mian'] = b64Img
        else:
            print("file %s not exits\n", imageFile)

        collection.insert_one(string)
        count += 1

print("保存mongodb成功, 一共有%d条数据", (count), end="\n")

