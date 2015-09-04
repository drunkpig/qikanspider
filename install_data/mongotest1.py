__author__ = 'cxu'
from pymongo import MongoClient
from bson.binary import Binary
import base64

db = MongoClient().db_test
collection = db.image_test

def save(imageFilePath):
    with open(imageFilePath, "rb") as image_file:
        bin_data = image_file.read()
        obj_id = collection.insert_one({"image": bin_data})

    return obj_id.inserted_id;

def get_bin_img(objid):
    data = collection.find_one({"_id": objid})
    image_bin = data['image']
    return image_bin

imgFile = "../cnki/img/0fbf6defca7f490792b83eeb9abf19ae.jpg"
objid = save(imgFile)
image_data = get_bin_img(objid)

imgB64Str = base64.b64encode(image_data).decode()
img_tag = '<img alt="sample" src="data:image/png;base64,{0}">'.format(imgB64Str)
print(img_tag)
