# Google Translate middleware
A PHP CLI tool to automate translations for PIM translatable attributes through Google Translate API

## Technical requirements

PHP 7.3 (minimal) installed locally

Or

Docker, see here for installing it on your computer ➡️ https://docs.docker.com/desktop/#download-and-install

## 1.Installation

open a command line shell.

Clone this repository : `git clone git@github.com:akeneo-presales/google_translate_middleware.git`

then go inside the project folder : `cd google_translate_middleware`

### 1.1 install the dependencies with PHP locally
run `php composer update`

### 1.2 install the dependencies trough Docker
Start the container
`docker-compose up -d`

Then run
`docker-compose exec php composer update`

## 2.Configuration

### 2.1 Environment variables Configuration

Before running the tool, you must set each environment variables listed inside the .env file situated at the root of the project

to begin, configure the PIM URL target and the related API connection information
```
PIM_URL=https://pim.url.com/
PIM_API_CLIENT_ID=your_pim_api_client_id
PIM_API_CLIENT_SECRET=your_pim_api_client_secret
PIM_API_USER=your_pim_api_username
PIM_API_PASSWORD=your_pim_api_password
```

Then you have to configure the locales source and destination for the translation
```
LOCALE_SOURCE=en_US
LOCALE_DESTINATION=fr_FR
```
Finally set the scope and the targeted attribute(s) separated by a comma
```
SCOPE_DESTINATION=ecommerce
TARGET_ATTRIBUTES=description,short_description
```

### 2.2 Google Translate API Configuration

To be abe to make request to the Google Translate API you must copy your Google API credentials file in JSON format at the root of the project, then set the name of the file in the **GOOGLE_TRANSLATE_CREDENTIALS_FILENAME** environement variable situated in the .env file. 

The content of the credentials file should be formatted like the example below :
```
{
  "type": "service_account",
  "project_id": "my-project-id",
  "private_key_id": "9999999999999999999999999999999999",
  "private_key": "-----BEGIN PRIVATE KEY-----\nzm9KqTrsqcQJojLPRcfEPw==\n-----END PRIVATE KEY-----\n",
  "client_email": "translate@my-project-id.iam.gserviceaccount.com",
  "client_id": "99999999999999999999",
  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
  "token_uri": "https://oauth2.googleapis.com/token",
  "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
  "client_x509_cert_url": "https://www.googleapis.com/robot/v1/metadata/x509/translate-2%40akecld-akeneo-presales-team.iam.gserviceaccount.com"
}
```

To obtain such a file follow the steps below :

First create a Service account (You must be administrator of the google project) :
- Go to https://console.cloud.google.com/iam-admin/serviceaccounts (Select your project)
- Create a new service account by clicking the button  **Create service account**
- On the step 1 set the Service account details (Name of the account, description)
- IMPORTANT : On the step 2 of the creation named "Grant this service account access to project", choose the role **Cloud Translation API User** then click continue
- On the step 3 click on the "Done" button

Then create the credentials json file this way (You must be administrator of the google project) :
- Go to https://console.cloud.google.com/apis/credentials (Select your project)
- Click on the newly service account you have created on the step before
- Select the **Keys** tab
- Click on the **Add Key** button then choose **Create new key** and select "JSON" as the key type.
- It should allow give you a json to download

## 3. Run the translator tool

### 3.1 with PHP locally
run `php translate.php`

### 3.2 trough Docker
Start the container if not yet started
`docker-compose up -d`

Then run
`docker-compose exec php php translate.php`



