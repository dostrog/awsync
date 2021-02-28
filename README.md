# AWsync

<h4> This is a test assigment project (B1)</h4>

[Description on Google Docs](https://docs.google.com/document/d/1dC0PrfmIbhP3EtG-3gwdto5vrv83m1DAmjSiNOSCAaQ/edit?usp=sharing)

------

## Installation

Project is written on 100% PHP and may be started from within project folder or from PHAR archive as a standalone application.

- Copy PHAR archive to the localhost
- Create `.env` file in the same folder where PHAR archive is

Example of .env file
```shell
DEFAULT_BASE_DIR="/assets"
DEFAULT_JOURNAL_DIR=""
RESOLVE_STRATEGY="LocalPriority"

AWS_ACCESS_KEY_ID="admin"
AWS_SECRET_ACCESS_KEY="admin"
AWS_REGION="eu-central-1"
AWS_ENDPONIT="http://127.0.0.1:9000"
```

### Demo

- Create test data with random number of files (with random data within). **NB! existing data may be overwritten! by using commands `polygon:aws` and `polygon:local`**
    ```shell
    $ ./awsync polygon bucket42
    ```
    It will populate test files on localhost (`./assets/bucket42`) and other number of test files on AWS S3 bucket named `bucket42`. There will be files with random names and sizes, files with the same name and size on both filesystems, files with the same name BUT different sizes on both filesystems.
    
    There are four options in .env file (using in `config/awsync.php`) to resolve conflicts with the third group of files (resolve strategies):
    ```
    // 'LocalPriority' - local file takes precedence / the local file will overwrite the remote one
    // 'AmazonPriority' - bucket's file takes precedence / the local file will be overwrote by the remote one
    // 'BiggerPriority' - file with bigger size takes precedence / bigger file will be on both filesystems
    // 'SmallerPriority' - file with smaller size takes precedence / smaller file will be on both filesystems
    ```
- Run command within terminal

```shell
$ ./awsync sync bucket42

Current status of folder/bucket : journal-bucket42.json
+-----------------+----------+-----------+
| Status          | Quantity | Total, Kb |
+-----------------+----------+-----------+
| on Amazon       | 29       | 1458      |
| on Local        | 37       | 1729      |
| synced          | 0        | 0         |
| unique basename | 45       | 2072      |
+-----------------+----------+-----------+

Sync files in local folder with AWS S3 Bucket...

 45/45 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%

+-----------------+----------+-----------+
| Status          | Quantity | Total, Kb |
+-----------------+----------+-----------+
| on Amazon       | 45       | 2072      |
| on Local        | 45       | 2072      |
| synced          | 45       | 2072      |
| unique basename | 45       | 2072      |
| Uploaded        | 20       | 855       |
| Downloaded      | 8        | 343       |
| Do not touched  | 17       | 874       |
+-----------------+----------+-----------+
```

## Using

Project is written on 100% PHP and may be started from within project folder or from PHAR archive as a standalone application. 

```shell
./awsync bucket42
```

## Help

```shell

$ ./awsync

Awsync  1.0.7

USAGE: awsync <command> [options] [arguments]

polygon       Populate test files with random data on local and AWS S3 filesystem
self-update   Allows to self-update a build application
sync          Sync files in local folder with AWS S3 Bucket

polygon:aws   Populate test files with random data on AWS S3
polygon:local Populate test files with random data on local filesystem
```
