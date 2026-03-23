# Foreign Key Delete Policies

This document explains what happens when you delete data from the database. In a database, many items are linked (e.g.,
a "Site" contains many "Stratigraphic Units"). When you delete a "parent" item (the Site), the system must decide what
happens to its "children" (the Units).

There are two main strategies used in this project:

### ✅ CASCADE (Automatic Cleanup)

When the parent is deleted, all its linked children are **automatically deleted** as well.

* **Pros:**
    * **Efficiency:** No manual cleanup is required.
    * **Consistency:** Prevents "orphaned" data that no longer belongs to anything.
* **Cons:**
    * **Accidental Loss:** If you delete a parent by mistake, you lose all its children permanently.
    * **Hidden Impact:** It might not be obvious how many records are being deleted at once.

### ⛔ RESTRICT (Safety Lock)

The system **blocks the deletion** of a parent if it still has any linked children. You must manually delete or reassign
the children before you can delete the parent.

* **Pros:**
    * **Data Safety:** Prevents accidental mass deletion of important data.
    * **Explicit Control:** Forces the user to acknowledge and handle dependent records.
* **Cons:**
    * **Extra Steps:** Requires more manual work to delete a complex hierarchy.
    * **Inconvenience:** Can be frustrating if you have many small dependent items that need to be cleared first.

---

> Source of truth: migration `Version20250621090503.php`.

---

## Auth

### User

| Table        | Column | References | On Delete |
|--------------|--------|------------|-----------|
| `auth.users` | —      | —          | —         |

The User table has no outgoing foreign keys. It is referenced by many other tables (see below).

### SiteUserPrivilege

| Column    | References         | On Delete | Meaning                                             |
|-----------|--------------------|-----------|-----------------------------------------------------|
| `user_id` | User               | CASCADE   | ✅ Removing a user deletes all their site privileges |
| `site_id` | ArchaeologicalSite | CASCADE   | ✅ Removing a site deletes all associated privileges |

### RefreshToken

No foreign keys (stores username as plain text).

---

## Core Data

### ArchaeologicalSite

| Column          | References          | On Delete | Meaning                                   |
|-----------------|---------------------|-----------|-------------------------------------------|
| `created_by_id` | User                | RESTRICT  | ⛔ Cannot delete a user who created a site |
| `region_id`     | Region (vocabulary) | RESTRICT  | ⛔ Cannot delete a region that is in use   |

### Context

| Column    | References         | On Delete | Meaning                                        |
|-----------|--------------------|-----------|------------------------------------------------|
| `site_id` | ArchaeologicalSite | RESTRICT  | ⛔ Cannot delete a site that still has contexts |

### StratigraphicUnit (SU)

| Column    | References         | On Delete | Meaning                                   |
|-----------|--------------------|-----------|-------------------------------------------|
| `site_id` | ArchaeologicalSite | RESTRICT  | ⛔ Cannot delete a site that still has SUs |

### Individual

| Column                  | References                  | On Delete | Meaning                                          |
|-------------------------|-----------------------------|-----------|--------------------------------------------------|
| `stratigraphic_unit_id` | StratigraphicUnit           | RESTRICT  | ⛔ Cannot delete an SU that still has individuals |
| `age_id`                | Individual Age (vocabulary) | RESTRICT  | ⛔ Cannot delete an age term that is in use       |

### MicrostratigraphicUnit (MU)

| Column                  | References        | On Delete | Meaning                                                        |
|-------------------------|-------------------|-----------|----------------------------------------------------------------|
| `stratigraphic_unit_id` | StratigraphicUnit | RESTRICT  | ⛔ Cannot delete an SU that still has micro-stratigraphic units |

### Pottery

| Column                  | References                            | On Delete | Meaning                                              |
|-------------------------|---------------------------------------|-----------|------------------------------------------------------|
| `stratigraphic_unit_id` | StratigraphicUnit                     | RESTRICT  | ⛔ Cannot delete an SU that still has pottery records |
| `surface_treatment_id`  | Surface Treatment (vocabulary)        | RESTRICT  | ⛔ Cannot delete a surface treatment term in use      |
| `cultural_context_id`   | Cultural Context (vocabulary)         | RESTRICT  | ⛔ Cannot delete a cultural context term in use       |
| `part_id`               | Pottery Shape (vocabulary)            | RESTRICT  | ⛔ Cannot delete a shape term in use                  |
| `functional_group_id`   | Pottery Functional Group (vocabulary) | RESTRICT  | ⛔ Cannot delete a functional group term in use       |
| `functional_form_id`    | Pottery Functional Form (vocabulary)  | RESTRICT  | ⛔ Cannot delete a functional form term in use        |

### Sample

| Column    | References               | On Delete | Meaning                                       |
|-----------|--------------------------|-----------|-----------------------------------------------|
| `site_id` | ArchaeologicalSite       | RESTRICT  | ⛔ Cannot delete a site that still has samples |
| `type_id` | Sample Type (vocabulary) | RESTRICT  | ⛔ Cannot delete a sample type term in use     |

### SedimentCore

| Column    | References   | On Delete | Meaning                                                       |
|-----------|--------------|-----------|---------------------------------------------------------------|
| `site_id` | SamplingSite | RESTRICT  | ⛔ Cannot delete a sampling site that still has sediment cores |

### SamplingSite

| Column      | References          | On Delete | Meaning                                 |
|-------------|---------------------|-----------|-----------------------------------------|
| `region_id` | Region (vocabulary) | RESTRICT  | ⛔ Cannot delete a region that is in use |

### SamplingStratigraphicUnit

| Column    | References   | On Delete | Meaning                                            |
|-----------|--------------|-----------|----------------------------------------------------|
| `site_id` | SamplingSite | RESTRICT  | ⛔ Cannot delete a sampling site that still has SUs |

### MediaObject

| Column           | References                     | On Delete | Meaning                                          |
|------------------|--------------------------------|-----------|--------------------------------------------------|
| `type_id`        | Media Object Type (vocabulary) | RESTRICT  | ⛔ Cannot delete a media type term in use         |
| `uploaded_by_id` | User                           | RESTRICT  | ⛔ Cannot delete a user who uploaded a media file |

### Analysis

| Column             | References                 | On Delete | Meaning                                        |
|--------------------|----------------------------|-----------|------------------------------------------------|
| `analysis_type_id` | Analysis Type (vocabulary) | RESTRICT  | ⛔ Cannot delete an analysis type term in use   |
| `created_by_id`    | User                       | RESTRICT  | ⛔ Cannot delete a user who created an analysis |

---

## Botany

### Charcoal

| Column                  | References                       | On Delete | Meaning                                             |
|-------------------------|----------------------------------|-----------|-----------------------------------------------------|
| `stratigraphic_unit_id` | StratigraphicUnit                | RESTRICT  | ⛔ Cannot delete an SU that still has charcoal items |
| `voc_taxonomy_id`       | Botany Taxonomy (vocabulary)     | RESTRICT  | ⛔ Cannot delete a botany taxonomy term in use       |
| `voc_element_id`        | Botany Element (vocabulary)      | RESTRICT  | ⛔ Cannot delete a botany element term in use        |
| `voc_element_part_id`   | Botany Element Part (vocabulary) | RESTRICT  | ⛔ Cannot delete a botany element part term in use   |

### Seed

| Column                  | References                       | On Delete | Meaning                                           |
|-------------------------|----------------------------------|-----------|---------------------------------------------------|
| `stratigraphic_unit_id` | StratigraphicUnit                | RESTRICT  | ⛔ Cannot delete an SU that still has seed items   |
| `voc_taxonomy_id`       | Botany Taxonomy (vocabulary)     | RESTRICT  | ⛔ Cannot delete a botany taxonomy term in use     |
| `voc_element_id`        | Botany Element (vocabulary)      | RESTRICT  | ⛔ Cannot delete a botany element term in use      |
| `voc_element_part_id`   | Botany Element Part (vocabulary) | RESTRICT  | ⛔ Cannot delete a botany element part term in use |

---

## Zoology

### Bone

| Column                  | References                 | On Delete | Meaning                                         |
|-------------------------|----------------------------|-----------|-------------------------------------------------|
| `stratigraphic_unit_id` | StratigraphicUnit          | RESTRICT  | ⛔ Cannot delete an SU that still has bone items |
| `voc_taxonomy_id`       | Zoo Taxonomy (vocabulary)  | RESTRICT  | ⛔ Cannot delete a zoo taxonomy term in use      |
| `voc_bone_id`           | Zoo Bone (vocabulary)      | RESTRICT  | ⛔ Cannot delete a zoo bone term in use          |
| `voc_bone_part_id`      | Zoo Bone Part (vocabulary) | RESTRICT  | ⛔ Cannot delete a zoo bone part term in use     |

### Tooth

| Column                  | References                | On Delete | Meaning                                          |
|-------------------------|---------------------------|-----------|--------------------------------------------------|
| `stratigraphic_unit_id` | StratigraphicUnit         | RESTRICT  | ⛔ Cannot delete an SU that still has tooth items |
| `voc_taxonomy_id`       | Zoo Taxonomy (vocabulary) | RESTRICT  | ⛔ Cannot delete a zoo taxonomy term in use       |
| `voc_tooth_id`          | Zoo Bone (vocabulary)     | RESTRICT  | ⛔ Cannot delete a zoo bone term in use           |

---

## History

### Animal (history citation)

| Column          | References                    | On Delete | Meaning                                      |
|-----------------|-------------------------------|-----------|----------------------------------------------|
| `age_id`        | History Language (vocabulary) | RESTRICT  | ⛔ Cannot delete a language term in use       |
| `animal_id`     | History Animal (vocabulary)   | RESTRICT  | ⛔ Cannot delete a history animal term in use |
| `location_id`   | History Location (vocabulary) | RESTRICT  | ⛔ Cannot delete a location in use            |
| `created_by_id` | User                          | RESTRICT  | ⛔ Cannot delete a user who created a record  |

### Plant (history citation)

| Column          | References                    | On Delete | Meaning                                     |
|-----------------|-------------------------------|-----------|---------------------------------------------|
| `age_id`        | History Language (vocabulary) | RESTRICT  | ⛔ Cannot delete a language term in use      |
| `plant_id`      | History Plant (vocabulary)    | RESTRICT  | ⛔ Cannot delete a history plant term in use |
| `location_id`   | History Location (vocabulary) | RESTRICT  | ⛔ Cannot delete a location in use           |
| `created_by_id` | User                          | RESTRICT  | ⛔ Cannot delete a user who created a record |

---

## Join / Association Tables

These tables link two entities together. They almost always use CASCADE on both sides,
meaning the row is automatically removed when either of the two linked records is deleted.

### ContextStratigraphicUnit

| Column       | References        | On Delete | Effect         |
|--------------|-------------------|-----------|----------------|
| `su_id`      | StratigraphicUnit | CASCADE   | ✅ Auto-deleted |
| `context_id` | Context           | CASCADE   | ✅ Auto-deleted |

### SampleStratigraphicUnit

| Column      | References        | On Delete | Effect         |
|-------------|-------------------|-----------|----------------|
| `sample_id` | Sample            | CASCADE   | ✅ Auto-deleted |
| `su_id`     | StratigraphicUnit | CASCADE   | ✅ Auto-deleted |

### SedimentCoreDepth

| Column             | References                | On Delete | Effect         |
|--------------------|---------------------------|-----------|----------------|
| `sediment_core_id` | SedimentCore              | CASCADE   | ✅ Auto-deleted |
| `su_id`            | SamplingStratigraphicUnit | CASCADE   | ✅ Auto-deleted |

### SiteCulturalContext

| Column                | References                    | On Delete | Effect         |
|-----------------------|-------------------------------|-----------|----------------|
| `site_id`             | ArchaeologicalSite            | CASCADE   | ✅ Auto-deleted |
| `cultural_context_id` | Cultural Context (vocabulary) | RESTRICT  | ⛔ Blocked      |

### PotteryDecoration

| Column          | References              | On Delete | Effect         |
|-----------------|-------------------------|-----------|----------------|
| `pottery_id`    | Pottery                 | CASCADE   | ✅ Auto-deleted |
| `decoration_id` | Decoration (vocabulary) | CASCADE   | ✅ Auto-deleted |

### StratigraphicUnitRelationship

| Column            | References               | On Delete | Effect         |
|-------------------|--------------------------|-----------|----------------|
| `lft_su_id`       | StratigraphicUnit        | CASCADE   | ✅ Auto-deleted |
| `relationship_id` | SU Relation (vocabulary) | RESTRICT  | ⛔ Blocked      |
| `rgt_su_id`       | StratigraphicUnit        | CASCADE   | ✅ Auto-deleted |

---

### Analysis Join Tables

All analysis join tables connect an Analysis to a subject entity.
Both sides use CASCADE: deleting either the analysis or the subject removes the join row.

| Join Table                      | Subject Entity     | analysis_id On Delete | subject_id On Delete |
|---------------------------------|--------------------|-----------------------|----------------------|
| AnalysisBotanyCharcoal          | Charcoal           | ✅ CASCADE             | ✅ CASCADE            |
| AnalysisBotanySeed              | Seed               | ✅ CASCADE             | ✅ CASCADE            |
| AnalysisContextBotany           | Context            | ✅ CASCADE             | ✅ CASCADE            |
| AnalysisContextZoo              | Context            | ✅ CASCADE             | ✅ CASCADE            |
| AnalysisIndividual              | Individual         | ✅ CASCADE             | ✅ CASCADE            |
| AnalysisPottery                 | Pottery            | ✅ CASCADE             | ✅ CASCADE            |
| AnalysisSampleMicrostratigraphy | Sample             | ✅ CASCADE             | ✅ CASCADE            |
| AnalysisSiteAnthropology        | ArchaeologicalSite | ✅ CASCADE             | ✅ CASCADE            |
| AnalysisZooBone                 | Bone               | ✅ CASCADE             | ✅ CASCADE            |
| AnalysisZooTooth                | Tooth              | ✅ CASCADE             | ✅ CASCADE            |

### Absolute Dating (inheritance from Analysis Joins)

Each absolute dating table extends an analysis join table via single-table inheritance on the `id` column.

| Abs Dating Table                | Parent Join Table      | On Delete |
|---------------------------------|------------------------|-----------|
| AbsDatingAnalysisBotanyCharcoal | AnalysisBotanyCharcoal | ✅ CASCADE |
| AbsDatingAnalysisBotanySeed     | AnalysisBotanySeed     | ✅ CASCADE |
| AbsDatingAnalysisIndividual     | AnalysisIndividual     | ✅ CASCADE |
| AbsDatingAnalysisPottery        | AnalysisPottery        | ✅ CASCADE |
| AbsDatingAnalysisZooBone        | AnalysisZooBone        | ✅ CASCADE |
| AbsDatingAnalysisZooTooth       | AnalysisZooTooth       | ✅ CASCADE |

### Analysis Context Taxonomy Joins

| Join Table                    | Column        | References                   | On Delete |
|-------------------------------|---------------|------------------------------|-----------|
| AnalysisContextBotanyTaxonomy | `analysis_id` | AnalysisContextBotany        | ✅ CASCADE |
| AnalysisContextBotanyTaxonomy | `taxonomy_id` | Botany Taxonomy (vocabulary) | ✅ CASCADE |
| AnalysisContextZooTaxonomy    | `analysis_id` | AnalysisContextZoo           | ✅ CASCADE |
| AnalysisContextZooTaxonomy    | `taxonomy_id` | Zoo Taxonomy (vocabulary)    | ✅ CASCADE |

### Media Object Join Tables

All media-object join tables use CASCADE on both sides.

| Join Table                           | Item Entity                   | media_object_id On Delete | item_id On Delete |
|--------------------------------------|-------------------------------|---------------------------|-------------------|
| MediaObjectAnalysis                  | Analysis                      | ✅ CASCADE                 | ✅ CASCADE         |
| MediaObjectHistoryLocation           | History Location (vocabulary) | ✅ CASCADE                 | ✅ CASCADE         |
| MediaObjectPottery                   | Pottery                       | ✅ CASCADE                 | ✅ CASCADE         |
| MediaObjectSamplingStratigraphicUnit | SamplingStratigraphicUnit     | ✅ CASCADE                 | ✅ CASCADE         |
| MediaObjectStratigraphicUnit         | StratigraphicUnit             | ✅ CASCADE                 | ✅ CASCADE         |

---

## Vocabulary Tables with Foreign Keys

Most vocabulary tables have no foreign keys (they are simple lookup lists).
The exceptions are listed below.

### History Animal (vocabulary)

| Column        | References                | On Delete | Meaning                                                        |
|---------------|---------------------------|-----------|----------------------------------------------------------------|
| `taxonomy_id` | Zoo Taxonomy (vocabulary) | RESTRICT  | ⛔ Cannot delete a zoo taxonomy term linked to a history animal |

### History Plant (vocabulary)

| Column        | References                   | On Delete | Meaning                                                          |
|---------------|------------------------------|-----------|------------------------------------------------------------------|
| `taxonomy_id` | Botany Taxonomy (vocabulary) | RESTRICT  | ⛔ Cannot delete a botany taxonomy term linked to a history plant |

### History Location (vocabulary)

| Column      | References          | On Delete | Meaning                                               |
|-------------|---------------------|-----------|-------------------------------------------------------|
| `region_id` | Region (vocabulary) | RESTRICT  | ⛔ Cannot delete a region linked to a history location |

### SU Relation (vocabulary)

| Column           | References                   | On Delete | Meaning                                                                      |
|------------------|------------------------------|-----------|------------------------------------------------------------------------------|
| `inverted_by_id` | SU Relation (self-reference) | RESTRICT  | ⛔ Cannot delete a relation term that is referenced as the inverse of another |

---

## General Design Principles

1. **Main data entities** (sites, SUs, contexts, samples, etc.) use **RESTRICT** towards their parent, preventing
   accidental deletion of important records that still have dependent data.

2. **Join / association tables** use **CASCADE** on both sides so that removing either linked entity automatically
   cleans up the association row.

3. **Vocabulary references** are almost always **RESTRICT**, ensuring that dictionary terms cannot be removed while
   still in use.

4. **User references** (`created_by_id`, `uploaded_by_id`) are **RESTRICT**, so a user account cannot be deleted while
   it owns data.

5. **Child data that only exists within an SU** (microstratigraphic units and pottery)
   now uses **RESTRICT** towards the SU, meaning an SU cannot be deleted while it still
   has dependent child records.

6. **Absolute dating tables** use **CASCADE** on the `id` foreign key back to their parent analysis join table,
   implementing table inheritance: when the parent join row is deleted, the dating extension row is deleted too.

---

## Reverse Perspective — What Depends on Each Entity?

The previous sections show outgoing foreign keys (what each table references).
This section flips the view: for every entity that is referenced by at least one foreign key,
it lists **all the tables that point to it** and what would happen if you tried to delete a row.

- If **any** dependent uses RESTRICT, the deletion will be **blocked** until those rows are removed first.
- CASCADE dependents are cleaned up automatically.

---

### User

| Dependent Table    | Column           | On Delete | Effect                                        |
|--------------------|------------------|-----------|-----------------------------------------------|
| ArchaeologicalSite | `created_by_id`  | RESTRICT  | ⛔ Blocked while the user owns a site          |
| Analysis           | `created_by_id`  | RESTRICT  | ⛔ Blocked while the user owns an analysis     |
| MediaObject        | `uploaded_by_id` | RESTRICT  | ⛔ Blocked while the user owns a media file    |
| Animal (history)   | `created_by_id`  | RESTRICT  | ⛔ Blocked while the user owns history animals |
| Plant (history)    | `created_by_id`  | RESTRICT  | ⛔ Blocked while the user owns history plants  |
| SiteUserPrivilege  | `user_id`        | CASCADE   | ✅ Privilege rows deleted automatically        |

> **Summary**: you must reassign or delete all data owned by the user before removing the account.

---

### ArchaeologicalSite

| Dependent Table          | Column       | On Delete | Effect                                     |
|--------------------------|--------------|-----------|--------------------------------------------|
| Context                  | `site_id`    | RESTRICT  | ⛔ Blocked while the site has contexts      |
| StratigraphicUnit        | `site_id`    | RESTRICT  | ⛔ Blocked while the site has SUs           |
| Sample                   | `site_id`    | RESTRICT  | ⛔ Blocked while the site has samples       |
| SiteCulturalContext      | `site_id`    | CASCADE   | ✅ Association rows deleted automatically   |
| SiteUserPrivilege        | `site_id`    | CASCADE   | ✅ Privilege rows deleted automatically     |
| AnalysisSiteAnthropology | `subject_id` | CASCADE   | ✅ Analysis join rows deleted automatically |

> **Summary**: you must delete all contexts, SUs and samples first; join/association rows are cleaned up automatically.

---

### Context

| Dependent Table          | Column       | On Delete | Effect                                     |
|--------------------------|--------------|-----------|--------------------------------------------|
| ContextStratigraphicUnit | `context_id` | CASCADE   | ✅ Association rows deleted automatically   |
| AnalysisContextBotany    | `subject_id` | CASCADE   | ✅ Analysis join rows deleted automatically |
| AnalysisContextZoo       | `subject_id` | CASCADE   | ✅ Analysis join rows deleted automatically |

> **Summary**: a context can be freely deleted — all dependents use CASCADE.

---

### StratigraphicUnit (SU)

| Dependent Table               | Column                  | On Delete | Effect                                         |
|-------------------------------|-------------------------|-----------|------------------------------------------------|
| Individual                    | `stratigraphic_unit_id` | RESTRICT  | ⛔ Blocked while the SU has individuals         |
| Pottery                       | `stratigraphic_unit_id` | RESTRICT  | ⛔ Blocked while the SU has pottery records     |
| Charcoal                      | `stratigraphic_unit_id` | RESTRICT  | ⛔ Blocked while the SU has charcoal items      |
| Seed                          | `stratigraphic_unit_id` | RESTRICT  | ⛔ Blocked while the SU has seed items          |
| Bone                          | `stratigraphic_unit_id` | RESTRICT  | ⛔ Blocked while the SU has bone items          |
| Tooth                         | `stratigraphic_unit_id` | RESTRICT  | ⛔ Blocked while the SU has tooth items         |
| MicrostratigraphicUnit        | `stratigraphic_unit_id` | RESTRICT  | ⛔ Blocked while the SU has MUs                 |
| ContextStratigraphicUnit      | `su_id`                 | CASCADE   | ✅ Association rows deleted automatically       |
| SampleStratigraphicUnit       | `su_id`                 | CASCADE   | ✅ Association rows deleted automatically       |
| StratigraphicUnitRelationship | `lft_su_id`             | CASCADE   | ✅ Relationship rows deleted automatically      |
| StratigraphicUnitRelationship | `rgt_su_id`             | CASCADE   | ✅ Relationship rows deleted automatically      |
| MediaObjectStratigraphicUnit  | `item_id`               | CASCADE   | ✅ Media association rows deleted automatically |

> **Summary**: you must remove all individuals, pottery, micro-stratigraphic units (MUs), charcoal, seeds, bones and
> teeth before deleting an SU.
> Association/join rows are cleaned up automatically.

---

### Individual

| Dependent Table    | Column       | On Delete | Effect                                     |
|--------------------|--------------|-----------|--------------------------------------------|
| AnalysisIndividual | `subject_id` | CASCADE   | ✅ Analysis join rows deleted automatically |

> **Summary**: an individual can be freely deleted — all dependents use CASCADE.

---

### Pottery

| Dependent Table    | Column       | On Delete | Effect                                         |
|--------------------|--------------|-----------|------------------------------------------------|
| PotteryDecoration  | `pottery_id` | CASCADE   | ✅ Decoration rows deleted automatically        |
| AnalysisPottery    | `subject_id` | CASCADE   | ✅ Analysis join rows deleted automatically     |
| MediaObjectPottery | `item_id`    | CASCADE   | ✅ Media association rows deleted automatically |

> **Summary**: a pottery record can be freely deleted — all dependents use CASCADE.

---

### Sample

| Dependent Table                 | Column       | On Delete | Effect                                     |
|---------------------------------|--------------|-----------|--------------------------------------------|
| SampleStratigraphicUnit         | `sample_id`  | CASCADE   | ✅ Association rows deleted automatically   |
| AnalysisSampleMicrostratigraphy | `subject_id` | CASCADE   | ✅ Analysis join rows deleted automatically |

> **Summary**: a sample can be freely deleted — all dependents use CASCADE.

---

### SamplingSite

| Dependent Table           | Column    | On Delete | Effect                                      |
|---------------------------|-----------|-----------|---------------------------------------------|
| SamplingStratigraphicUnit | `site_id` | RESTRICT  | ⛔ Blocked while the sampling site has SUs   |
| SedimentCore              | `site_id` | RESTRICT  | ⛔ Blocked while the sampling site has cores |

> **Summary**: you must delete all sampling SUs and sediment cores before removing a sampling site.

---

### SamplingStratigraphicUnit

| Dependent Table                      | Column    | On Delete | Effect                                         |
|--------------------------------------|-----------|-----------|------------------------------------------------|
| SedimentCoreDepth                    | `su_id`   | CASCADE   | ✅ Depth rows deleted automatically             |
| MediaObjectSamplingStratigraphicUnit | `item_id` | CASCADE   | ✅ Media association rows deleted automatically |

> **Summary**: a sampling SU can be freely deleted — all dependents use CASCADE.

---

### SedimentCore

| Dependent Table   | Column             | On Delete | Effect                             |
|-------------------|--------------------|-----------|------------------------------------|
| SedimentCoreDepth | `sediment_core_id` | CASCADE   | ✅ Depth rows deleted automatically |

> **Summary**: a sediment core can be freely deleted — all dependents use CASCADE.

---

### Analysis

| Dependent Table                 | Column        | On Delete | Effect                                         |
|---------------------------------|---------------|-----------|------------------------------------------------|
| AnalysisBotanyCharcoal          | `analysis_id` | CASCADE   | ✅ Join rows deleted automatically              |
| AnalysisBotanySeed              | `analysis_id` | CASCADE   | ✅ Join rows deleted automatically              |
| AnalysisContextBotany           | `analysis_id` | CASCADE   | ✅ Join rows deleted automatically              |
| AnalysisContextZoo              | `analysis_id` | CASCADE   | ✅ Join rows deleted automatically              |
| AnalysisIndividual              | `analysis_id` | CASCADE   | ✅ Join rows deleted automatically              |
| AnalysisPottery                 | `analysis_id` | CASCADE   | ✅ Join rows deleted automatically              |
| AnalysisSampleMicrostratigraphy | `analysis_id` | CASCADE   | ✅ Join rows deleted automatically              |
| AnalysisSiteAnthropology        | `analysis_id` | CASCADE   | ✅ Join rows deleted automatically              |
| AnalysisZooBone                 | `analysis_id` | CASCADE   | ✅ Join rows deleted automatically              |
| AnalysisZooTooth                | `analysis_id` | CASCADE   | ✅ Join rows deleted automatically              |
| MediaObjectAnalysis             | `item_id`     | CASCADE   | ✅ Media association rows deleted automatically |

> **Summary**: an analysis can be freely deleted — all dependents (joins, media links) use CASCADE.

---

### MediaObject

| Dependent Table                      | Column            | On Delete | Effect                                   |
|--------------------------------------|-------------------|-----------|------------------------------------------|
| MediaObjectAnalysis                  | `media_object_id` | CASCADE   | ✅ Association rows deleted automatically |
| MediaObjectHistoryLocation           | `media_object_id` | CASCADE   | ✅ Association rows deleted automatically |
| MediaObjectPottery                   | `media_object_id` | CASCADE   | ✅ Association rows deleted automatically |
| MediaObjectSamplingStratigraphicUnit | `media_object_id` | CASCADE   | ✅ Association rows deleted automatically |
| MediaObjectStratigraphicUnit         | `media_object_id` | CASCADE   | ✅ Association rows deleted automatically |

> **Summary**: a media object can be freely deleted — all dependents use CASCADE.

---

### Charcoal

| Dependent Table        | Column       | On Delete | Effect                                     |
|------------------------|--------------|-----------|--------------------------------------------|
| AnalysisBotanyCharcoal | `subject_id` | CASCADE   | ✅ Analysis join rows deleted automatically |

> **Summary**: a charcoal record can be freely deleted.

---

### Seed

| Dependent Table    | Column       | On Delete | Effect                                     |
|--------------------|--------------|-----------|--------------------------------------------|
| AnalysisBotanySeed | `subject_id` | CASCADE   | ✅ Analysis join rows deleted automatically |

> **Summary**: a seed record can be freely deleted.

---

### Bone

| Dependent Table | Column       | On Delete | Effect                                     |
|-----------------|--------------|-----------|--------------------------------------------|
| AnalysisZooBone | `subject_id` | CASCADE   | ✅ Analysis join rows deleted automatically |

> **Summary**: a bone record can be freely deleted.

---

### Tooth

| Dependent Table  | Column       | On Delete | Effect                                     |
|------------------|--------------|-----------|--------------------------------------------|
| AnalysisZooTooth | `subject_id` | CASCADE   | ✅ Analysis join rows deleted automatically |

> **Summary**: a tooth record can be freely deleted.

---

### Analysis Join Tables (as referenced by Absolute Dating)

| Referenced Join Table  | Dependent (Abs Dating)          | On Delete | Effect                             |
|------------------------|---------------------------------|-----------|------------------------------------|
| AnalysisBotanyCharcoal | AbsDatingAnalysisBotanyCharcoal | CASCADE   | ✅ Dating row deleted automatically |
| AnalysisBotanySeed     | AbsDatingAnalysisBotanySeed     | CASCADE   | ✅ Dating row deleted automatically |
| AnalysisIndividual     | AbsDatingAnalysisIndividual     | CASCADE   | ✅ Dating row deleted automatically |
| AnalysisPottery        | AbsDatingAnalysisPottery        | CASCADE   | ✅ Dating row deleted automatically |
| AnalysisZooBone        | AbsDatingAnalysisZooBone        | CASCADE   | ✅ Dating row deleted automatically |
| AnalysisZooTooth       | AbsDatingAnalysisZooTooth       | CASCADE   | ✅ Dating row deleted automatically |

---

### AnalysisContextBotany / AnalysisContextZoo (as referenced by Taxonomy Joins)

| Referenced Table      | Dependent Table               | Column        | On Delete | Effect                                |
|-----------------------|-------------------------------|---------------|-----------|---------------------------------------|
| AnalysisContextBotany | AnalysisContextBotanyTaxonomy | `analysis_id` | CASCADE   | ✅ Taxonomy join deleted automatically |
| AnalysisContextZoo    | AnalysisContextZooTaxonomy    | `analysis_id` | CASCADE   | ✅ Taxonomy join deleted automatically |

---

### Vocabulary Tables — Reverse View

Most vocabulary tables are referenced with RESTRICT, meaning the term cannot be deleted while in use.

| Vocabulary Table         | Dependent Table(s)                                                  | On Delete           | Effect         |
|--------------------------|---------------------------------------------------------------------|---------------------|----------------|
| Region                   | ArchaeologicalSite, SamplingSite, History Location (voc)            | all RESTRICT        | ⛔ Blocked      |
| Analysis Type            | Analysis                                                            | RESTRICT            | ⛔ Blocked      |
| Individual Age           | Individual                                                          | RESTRICT            | ⛔ Blocked      |
| Media Object Type        | MediaObject                                                         | RESTRICT            | ⛔ Blocked      |
| Surface Treatment        | Pottery                                                             | RESTRICT            | ⛔ Blocked      |
| Cultural Context         | Pottery, SiteCulturalContext                                        | RESTRICT            | ⛔ Blocked      |
| Pottery Shape            | Pottery                                                             | RESTRICT            | ⛔ Blocked      |
| Pottery Functional Group | Pottery                                                             | RESTRICT            | ⛔ Blocked      |
| Pottery Functional Form  | Pottery                                                             | RESTRICT            | ⛔ Blocked      |
| Sample Type              | Sample                                                              | RESTRICT            | ⛔ Blocked      |
| Decoration               | PotteryDecoration                                                   | CASCADE             | ✅ Auto-deleted |
| Botany Taxonomy          | Charcoal, Seed, History Plant (voc), AnalysisContextBotanyTaxonomy  | RESTRICT / CASCADE* | ⛔ Blocked*     |
| Botany Element           | Charcoal, Seed                                                      | RESTRICT            | ⛔ Blocked      |
| Botany Element Part      | Charcoal, Seed                                                      | RESTRICT            | ⛔ Blocked      |
| Zoo Taxonomy             | Bone, Tooth, History Animal (voc), AnalysisContextZooTaxonomy       | RESTRICT / CASCADE* | ⛔ Blocked*     |
| Zoo Bone                 | Bone (`voc_bone_id`), Tooth (`voc_tooth_id`)                        | RESTRICT            | ⛔ Blocked      |
| Zoo Bone Part            | Bone                                                                | RESTRICT            | ⛔ Blocked      |
| History Language         | Animal (history), Plant (history)                                   | RESTRICT            | ⛔ Blocked      |
| History Animal (voc)     | Animal (history)                                                    | RESTRICT            | ⛔ Blocked      |
| History Plant (voc)      | Plant (history)                                                     | RESTRICT            | ⛔ Blocked      |
| History Location (voc)   | Animal (history), Plant (history), MediaObjectHistoryLocation       | RESTRICT / CASCADE* | ⛔ Blocked*     |
| SU Relation              | StratigraphicUnitRelationship, SU Relation (self: `inverted_by_id`) | RESTRICT            | ⛔ Blocked      |

> \* These vocabulary tables have a mix: main data tables use RESTRICT (blocking deletion)
> while some join/taxonomy tables use CASCADE (rows cleaned up automatically).
> The RESTRICT dependents still block deletion, so the term cannot be removed while in use.
