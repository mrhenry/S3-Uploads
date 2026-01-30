# GS Uploads

> Lightweight "drop-in" for storing WordPress uploads on Google Cloud Storage instead of the local filesystem.

Getting Set Up
==========

```PHP
define( 'S3_UPLOADS_BUCKET', 'my-bucket' );
define( 'GS_UPLOADS_BUCKET_URL', 'https://my-bucket.example.com' ); // optional
```

Offline Development
=======

While it's possible to use GS Uploads for local development (this is actually a nice way to not have to sync all uploads from production to development), if you want to develop offline you can simply not set `S3_UPLOADS_BUCKET`.

-----

Originally fork from : [humanmade/S3-Uploads](https://github.com/humanmade/S3-Uploads)
