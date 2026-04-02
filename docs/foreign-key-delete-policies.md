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

## What Depends on Each Entity?

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

| Dependent Table                     | Column        | On Delete | Effect                                         |
|-------------------------------------|---------------|-----------|------------------------------------------------|
| AnalysisBotanyCharcoal              | `analysis_id` | CASCADE   | ✅ Join rows deleted automatically              |
| AnalysisBotanySeed                  | `analysis_id` | CASCADE   | ✅ Join rows deleted automatically              |
| AnalysisContextBotany               | `analysis_id` | CASCADE   | ✅ Join rows deleted automatically              |
| AnalysisContextZoo                  | `analysis_id` | CASCADE   | ✅ Join rows deleted automatically              |
| AnalysisIndividual                  | `analysis_id` | CASCADE   | ✅ Join rows deleted automatically              |
| AnalysisPottery                     | `analysis_id` | CASCADE   | ✅ Join rows deleted automatically              |
| AnalysisSampleMicrostratigraphy     | `analysis_id` | CASCADE   | ✅ Join rows deleted automatically              |
| AnalysisSiteAnthropology            | `analysis_id` | CASCADE   | ✅ Join rows deleted automatically              |
| AnalysisZooBone                     | `analysis_id` | CASCADE   | ✅ Join rows deleted automatically              |
| AnalysisZooTooth                    | `analysis_id` | CASCADE   | ✅ Join rows deleted automatically              |
| MediaObjectAnalysis                 | `item_id`     | CASCADE   | ✅ Media association rows deleted automatically |
| MediaObjectPaleoclimateSample       | `item_id`     | CASCADE   | ✅ Media association rows deleted automatically |
| MediaObjectPaleoclimateSamplingSite | `item_id`     | CASCADE   | ✅ Media association rows deleted automatically |

> **Summary**: an analysis can be freely deleted — all dependents (joins, media links) use CASCADE.

---

### PaleoclimateSamplingSite

| Dependent Table                     | Column    | On Delete | Effect                                         |
|-------------------------------------|-----------|-----------|------------------------------------------------|
| PaleoclimateSample                  | `site_id` | RESTRICT  | ⛔ Blocked while the sampling site has samples  |
| MediaObjectPaleoclimateSamplingSite | `item_id` | CASCADE   | ✅ Media association rows deleted automatically |

> **Summary**: you must delete all paleoclimate samples first; media association rows are cleaned up automatically.

---

### PaleoclimateSample

| Dependent Table               | Column    | On Delete | Effect                                         |
|-------------------------------|-----------|-----------|------------------------------------------------|
| MediaObjectPaleoclimateSample | `item_id` | CASCADE   | ✅ Media association rows deleted automatically |

> **Summary**: a paleoclimate sample can be freely deleted — all dependents use CASCADE.

---

### MediaObject

| Dependent Table                      | Column            | On Delete | Effect                                   |
|--------------------------------------|-------------------|-----------|------------------------------------------|
| MediaObjectAnalysis                  | `media_object_id` | CASCADE   | ✅ Association rows deleted automatically |
| MediaObjectPaleoclimateSample        | `media_object_id` | CASCADE   | ✅ Association rows deleted automatically |
| MediaObjectPaleoclimateSamplingSite  | `media_object_id` | CASCADE   | ✅ Association rows deleted automatically |
| MediaObjectHistoryLocation           | `media_object_id` | CASCADE   | ✅ Association rows deleted automatically |
| MediaObjectPottery                   | `media_object_id` | CASCADE   | ✅ Association rows deleted automatically |
| MediaObjectSamplingStratigraphicUnit | `media_object_id` | CASCADE   | ✅ Association rows deleted automatically |
| MediaObjectStratigraphicUnit         | `media_object_id` | CASCADE   | ✅ Association rows deleted automatically |

> **Summary**: a media object can be freely deleted — all dependents use CASCADE.

---

### WrittenSource

| Dependent Table             | Column              | On Delete | Effect                                     |
|-----------------------------|---------------------|-----------|--------------------------------------------|
| WrittenSourceCentury        | `written_source_id` | CASCADE   | ✅ Century join rows deleted automatically |
| WrittenSourceCitedWork      | `written_source_id` | RESTRICT  | ⛔ Blocked while it has cited works        |

> **Summary**: you must delete all cited works before removing a written source; century links are cleaned up automatically.

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

| Vocabulary Table         | Dependent Table(s)                                                                 | On Delete           | Effect         |
|--------------------------|------------------------------------------------------------------------------------|---------------------|----------------|
| Region                   | ArchaeologicalSite, SamplingSite, PaleoclimateSamplingSite, History Location (voc) | all RESTRICT        | ⛔ Blocked      |
| Century                  | WrittenSourceCentury                                                               | RESTRICT            | ⛔ Blocked      |
| Analysis Type            | Analysis                                                                           | RESTRICT            | ⛔ Blocked      |
| Individual Age           | Individual                                                                         | RESTRICT            | ⛔ Blocked      |
| Media Object Type        | MediaObject                                                                        | RESTRICT            | ⛔ Blocked      |
| Surface Treatment        | Pottery                                                                            | RESTRICT            | ⛔ Blocked      |
| Cultural Context         | Pottery, SiteCulturalContext                                                       | RESTRICT            | ⛔ Blocked      |
| Pottery Shape            | Pottery                                                                            | RESTRICT            | ⛔ Blocked      |
| Pottery Functional Group | Pottery                                                                            | RESTRICT            | ⛔ Blocked      |
| Pottery Functional Form  | Pottery                                                                            | RESTRICT            | ⛔ Blocked      |
| Sample Type              | Sample                                                                             | RESTRICT            | ⛔ Blocked      |
| Decoration               | PotteryDecoration                                                                  | CASCADE             | ✅ Auto-deleted |
| Botany Taxonomy          | Charcoal, Seed, History Plant (voc), AnalysisContextBotanyTaxonomy                 | RESTRICT / CASCADE* | ⛔ Blocked*     |
| Botany Element           | Charcoal, Seed                                                                     | RESTRICT            | ⛔ Blocked      |
| Botany Element Part      | Charcoal, Seed                                                                     | RESTRICT            | ⛔ Blocked      |
| Zoo Taxonomy             | Bone, Tooth, History Animal (voc), AnalysisContextZooTaxonomy                      | RESTRICT / CASCADE* | ⛔ Blocked*     |
| Zoo Bone                 | Bone (`voc_bone_id`), Tooth (`voc_tooth_id`)                                       | RESTRICT            | ⛔ Blocked      |
| Zoo Bone Part            | Bone                                                                               | RESTRICT            | ⛔ Blocked      |
| History Language         | Animal (history), Plant (history)                                                  | RESTRICT            | ⛔ Blocked      |
| History Animal (voc)     | Animal (history)                                                                   | RESTRICT            | ⛔ Blocked      |
| History Plant (voc)      | Plant (history)                                                                    | RESTRICT            | ⛔ Blocked      |
| History Author (voc)     | WrittenSource                                                                      | RESTRICT            | ⛔ Blocked      |
| History Cited Work (voc) | WrittenSourceCitedWork                                                             | RESTRICT            | ⛔ Blocked      |
| Written Source Type (voc)| WrittenSource                                                                      | RESTRICT            | ⛔ Blocked      |
| History Location (voc)   | Animal (history), Plant (history), MediaObjectHistoryLocation                      | RESTRICT / CASCADE* | ⛔ Blocked*     |
| SU Relation              | StratigraphicUnitRelationship, SU Relation (self: `inverted_by_id`)                | RESTRICT            | ⛔ Blocked      |

> \* These vocabulary tables have a mix: main data tables use RESTRICT (blocking deletion)
> while some join/taxonomy tables use CASCADE (rows cleaned up automatically).
> The RESTRICT dependents still block deletion, so the term cannot be removed while in use.
