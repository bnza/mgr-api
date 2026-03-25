# Authorization and Security Policies

This document outlines the authorization rules and security conditions for the MGR-API. It details who can perform
specific actions (Read, Create, Update, Delete) on different resources within the system.

## General Principles

1. **Administrator Access**: Users with `ROLE_ADMIN` generally have full access to all resources unless otherwise
   specified.
2. **Public Access**: Many data resources are publicly readable without authentication.
3. **Site-Specific Privileges**: Access to archaeological data is often tied to privileges granted on a per-site
   basis. There are two levels of site privileges:
    * **User**: Grants general access to participate in site data management. Most data modifications (Contexts,
      Stratigraphic Units, Samples, specialist items, etc.) only require this level.
    * **Editor**: A higher privilege level required exclusively for modifying or deleting the **Archaeological Site**
      record itself. "Site-specific editor privileges" means the user has been explicitly granted "Editor" access for a
      specific site.
4. **Specialist Roles**: Certain data types require specific professional roles (e.g., `ROLE_ARCHAEOBOTANIST` for
   Botany, `ROLE_ZOO_ARCHAEOLOGIST` for Zoo-archaeology, `ROLE_ANTHROPOLOGIST` for human remains,
   `ROLE_CERAMIC_SPECIALIST` for pottery, `ROLE_GEO_ARCHAEOLOGIST` for sediment cores, and `ROLE_MICROSTRATIGRAPHIST`
   for microstratigraphy).

---

## Archaeological Data

### Archaeological Sites

* **Read**: Publicly accessible.
* **Create**: Requires authentication, `ROLE_EDITOR`, and a specialist role (Field Director or Anthropologist).
* **Update**: Requires `ROLE_ADMIN` or site-specific **Editor** privileges.
* **Delete**: Requires `ROLE_ADMIN` OR all of the following:
    * The user must be the original creator of the site record.
    * The user must have `ROLE_EDITOR`.
    * The user must have site-specific **Editor** privileges for that site.

### Analysis Records

* **Read**: Publicly accessible.
* **Create**: Requires `ROLE_ADMIN` or a specialist role (Archaeobotanist, Zoo-archaeologist, Anthropologist, Ceramic
  Specialist, Geo-archaeologist, or Historian).

5. **Paleoclimate Specialists**: Users with `ROLE_PALEOCLIMATOLOGIST` have specialized access to paleoclimate sampling
   sites and samples. Unlike other archaeological data, these do not currently use site-specific privileges.

* **Update / Delete**: Requires `ROLE_ADMIN` or being the original creator of the analysis record.

### Contexts

* **Read**: Publicly accessible.
* **Create / Update / Delete**: Requires `ROLE_ADMIN` or site-specific **User** privileges on the related site.

### Stratigraphic Units (SU)

* **Read**: Publicly accessible.
* **Create / Update / Delete**: Requires `ROLE_ADMIN` or site-specific **User** privileges on the related site.

### Samples

* **Read**: Publicly accessible.
* **Create / Update / Delete**: Requires `ROLE_ADMIN` or site-specific **User** privileges on the related site.

### Specialist Data Items (Botany, Zoo, Pottery, etc.)

Specialist data includes detailed analyses of different materials. The following rules apply to items like:

* **Botany**: Charcoal and Seeds.
* **Zoo-archaeology**: Animal Bones and Teeth.
* **Pottery**: Ceramics and vessels.
* **Anthropology**: Human remains (Individuals).
* **Microstratigraphy**: Thin sections and micro-context units.
* **Geo-archaeology**: Sediment core depth units.

For all these specialist items:

* **Read**: Publicly accessible.
* **Create / Update / Delete**: Requires `ROLE_ADMIN` OR both of the following:
    * The user must have the corresponding specialist role (e.g., `ROLE_ARCHAEOBOTANIST` for botany).
    * The user must have site-specific **User** privileges on the related site (via the parent Stratigraphic Unit).

### Sediment Cores

* **Read**: Publicly accessible.
* **Create / Update / Delete**: Requires `ROLE_ADMIN` OR both of the following:
    * The user must have `ROLE_GEO_ARCHAEOLOGIST`.
    * The user must have site-specific **User** privileges on the related site.

### Paleoclimate Data

#### Paleoclimate Sampling Sites

* **Read**: Publicly accessible.
* **Create / Update / Delete**: Requires `ROLE_ADMIN` OR both of the following:
    * The user must have `ROLE_PALEOCLIMATOLOGIST`.
    * The user must have `ROLE_EDITOR`.

#### Paleoclimate Samples

* **Read**: Publicly accessible.
* **Create / Update / Delete**: Requires `ROLE_ADMIN` or `ROLE_PALEOCLIMATOLOGIST`.

### Historical Items

* **Read**: Publicly accessible.
* **Create / Update / Delete**: Requires `ROLE_ADMIN` or `ROLE_HISTORIAN`.

### Taxonomies and Vocabularies

* **Read**: Publicly accessible.
* **Create / Update / Delete**: Requires `ROLE_ADMIN` OR a combination of `ROLE_EDITOR` AND the relevant specialist
  role (e.g., `ROLE_ARCHAEOBOTANIST` for Botany taxonomies).

---

## Media and Files

### Media Objects (Uploads)

* **Read**: Publicly accessible.
* **Create**: Any fully authenticated user can upload media.
* **Update / Delete**: Requires `ROLE_ADMIN` or being the original uploader.

### Media Associations (Joins)

* **Read**: Requires full authentication.
* **Create / Update / Delete**: Delegated to the **Update** permission of the resource the media is being attached to (
  e.g., to attach a photo to a Site, you must be able to update that Site).

---

## Security and Administration

### Users

* **Read**: Requires `ROLE_ADMIN`.
* **Create / Update / Delete**: Requires `ROLE_ADMIN`.
    * *Self-Restriction*: An administrator cannot perform these actions on their own account to prevent accidental
      lockout or privilege escalation.

### Site User Privileges (Managing Access)

* **Read**: Requires `ROLE_ADMIN` or `ROLE_EDITOR`.
* **Create / Update / Delete**: Requires `ROLE_ADMIN` OR all of the following:
    * The user must have `ROLE_EDITOR`.
    * The user must be the original creator of the site.
    * The user cannot change their own privileges.

---

## Relationships and Joins

* **Stratigraphic Unit Relationships**:
    * **Read**: Requires full authentication.
    * **C/U/D**: Permissions are delegated to the first (left) Stratigraphic Unit in the relationship.
* **Resource Joins (e.g., Analysis Joins)**:
    * **Read**: Publicly accessible.
    * **C/U/D**: Typically delegated to the permissions of the underlying subject (e.g., the specific Botany item or
      Analysis being joined).
