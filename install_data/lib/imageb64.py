__author__ = 'cxu'
import base64

def image_b64_encode(imageFile):
    with open(imageFile, "rb") as image_file:
        encoded_string = base64.b64encode(image_file.read())

    return encoded_string


def image_b64_decode(mongoStr):
    return mongoStr.decode()
