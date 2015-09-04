import base64

__author__ = 'cxu'
from pymongo import MongoClient

db = MongoClient().db_test
collection = db.image_test

def insert_image(imageFile):
    with open(imageFile, "rb") as image_file:
        encoded_string = base64.b64encode(image_file.read())
        # print(encoded_string, end="\n")
        obj_id = collection.insert_one({"image": encoded_string})
        #  obj_id = collection.insert_one({"image": image_file.read()})
    return obj_id.inserted_id;

def retrieve_image(obj_id):
    data = collection.find_one({"_id": obj_id})
    # print("get image data is %s\n" % data)
    #  data_json = json.loads(data)
    #  img1 = data_json['image']
    #  decode=img1.decode()
    imgB64Str = data['image']
    imgB64Str = imgB64Str.decode()
    img_tag = '<img alt="sample" src="data:image/png;base64,{0}">'.format(imgB64Str)
    print(img_tag)
    return imgB64Str

imgFile = "../cnki/img/0fbf6defca7f490792b83eeb9abf19ae.jpg"

obj_id = insert_image(imgFile)
# print("inserted id %s" % obj_id, end="\n")
string = retrieve_image(obj_id)
# print(string)
