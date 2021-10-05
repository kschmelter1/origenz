<div class="design-step" v-if="step==1">
  <div class="dna dna-design" id="dna-anim">
    <?php get_template_part('templates/theme/part','dna'); ?>
  </div>
    <div class="design-step-divider"><div class="container-fluid">
        <h2>input your <u>ethnicity</u></h2>

        <div class="design-step-section bulleted">
          <div class="bullet">1</div>
            <h3>where did you have your dna test processed?</h3>

            <div class="dna-processed-by-wrapper">
                <input type="radio" name="dna-processed-by" id="dna-processed-by-ancestrydna" value="AncestryDNA" v-model="dnaProcessedBy">
                <label for="dna-processed-by-ancestrydna" class="btn btn-outline-primary">AncestryDNA</label>

                <input type="radio" name="dna-processed-by" id="dna-processed-by-23andme" value="23andMe" v-model="dnaProcessedBy">
                <label for="dna-processed-by-23andme" class="btn btn-outline-primary">23andMe</label>

                <input type="radio" name="dna-processed-by" id="dna-processed-by-somewhere-else" value="Somewhere else" v-model="dnaProcessedBy">
                <label for="dna-processed-by-somewhere-else" class="btn btn-outline-primary">Manual Input</label>
            </div>
        </div>
    </div></div>
    <div class="design-step-divider" v-if="showRegionsSection"> <div class="container-fluid">
        <div class="design-step-section bulleted">
          <div class="bullet">2</div>
            <h3>what were your results?</h3>

            <div class="design-region-wrapper">
                <div class="design-region-item" v-for="(region, regionIndex) in regions">
                    <div v-if="region.errors.length>0" class="text-danger">
                        <div v-for="error in region.errors">{{error}}</div>
                    </div>
                    <div class="select-wrapper">
                    <select v-model="region.region">
                        <option value=""></option>
                        <template v-for="regionGroup in regionListByGroup">
                            <optgroup v-if="regionGroup.regions.length>1" :label="regionGroup.group">
                                <option v-for="regionGroupItem in regionGroup.regions" :value="regionGroupItem.name">{{regionGroupItem.name}}</option>
                            </optgroup>
                            <option v-else :value="regionGroup.regions[0].name">{{regionGroup.regions[0].name}}</option>
                        </template>
                    </select>
                    </div>
                    <div class="percent"><input type="number" min="1" max="100" step="0.01" v-model.number="region.percent" @change="regionPercentChanged(regionIndex)" /></div>
                    <a class="remove-this" v-if="regions.length>1" @click.prevent="removeRegion(regionIndex)"><i class="fa fa-times"></i></a>
                </div>
            </div>
            <div class="design-region-add" v-if="regions.length<5 && totalCountries<5">
                <a href="#" @click.prevent="addRegion" class="add-more">Add Another Region</a>
            </div>
        </div>
      </div></div>
      <div class="design-step-divider" v-if="regions[0].region!=''"><div class="container-fluid">
        <div class="design-step-section bulleted">
          <div class="bullet">{{countrySectionBulletNumber}}</div>
            <h3>what countries are you from?</h3>

            <div class="design-country-region-wrapper">
                <div class="design-country-region-item" v-for="(region, regionIndex) in regions">
                    <div class="design-country-region-name">{{region.region}}</div>

                    <div class="design-country-list-wrapper">
                        <div class="design-country-list-item" v-for="(country, countryIndex) in region.countries">
                            <div v-if="country.errors.length>0" class="text-danger">
                                <div v-for="error in country.errors">{{error}}</div>
                            </div>
                            <div class="select-wrapper">
                            <select v-model="country.country">
                                <option value=""></option>
                                <option
                                    v-for="countryName in getCountriesInRegion(region.region)"
                                    :disabled="isCountrySelectedInRegion(countryName, countryIndex, regionIndex)"
                                    :value="countryName">{{countryName}}</option>
                            </select>
                            </div>
                            <div class="percent"><input type="number" min="1" max="100" step="0.01" v-model.number="country.percent" /></div>
                            <a class="remove-this" v-if="region.countries.length>1" @click.prevent="removeCountry(regionIndex, countryIndex)"><i class="fa fa-times"></i></a>
                        </div>
                    </div>

                    <div class="design-country-list-add" v-if="totalCountries<5">
                        <a href="#" @click.prevent="addCountry(regionIndex)" class="add-more">Add Another Country</a>
                    </div>
                </div>
            </div>
        </div>
      </div></div>
      <div class="container-fluid">
        <div class="design-step-section">
            <div v-if="errors.length>0" class="text-danger">
                <div v-for="error in errors">{{error}}</div>
            </div>
            <button type="button" @click="validateStep(1)" class="btn btn-primary">Continue to Step 2</button>
        </div>
      </div>

</div>
