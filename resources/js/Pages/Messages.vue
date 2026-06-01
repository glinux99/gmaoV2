<template>
  <AppLayout>
    <Head title="Messagerie Interne - Enterprise Hub" />

    <div
      class="h-[calc(100vh-5rem)] flex flex-col overflow-hidden font-sans transition-colors duration-300"
      :class="theme === 'dark' ? 'bg-gray-900 text-gray-100' : 'bg-slate-100'"
    >
      <input ref="fileInput" type="file" class="hidden" multiple @change="handleFileChange" />

      <div class="flex-1 flex overflow-hidden relative">
        <!-- SIDEBAR GAUCHE (conversations) -->
        <div
          :class="[
            'w-80 flex flex-col flex-shrink-0 absolute lg:relative z-20 h-full transition-all duration-300 shadow-2xl lg:shadow-none',
            isMobileNavOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
            theme === 'dark' ? 'bg-gray-800 border-r border-gray-700' : 'bg-white border-r border-slate-200',
          ]"
        >
          <div class="p-4 border-b" :class="theme === 'dark' ? 'border-gray-700 bg-gray-800/50' : 'border-slate-100 bg-slate-50/50'">
            <InputGroup class="rounded-xl overflow-hidden shadow-sm" :class="theme === 'dark' ? 'bg-gray-700 border-gray-600' : 'bg-white border-slate-200'">
              <InputGroupAddon class="bg-transparent border-0 px-3">
                <i class="pi pi-search" :class="theme === 'dark' ? 'text-gray-400' : 'text-slate-400'"></i>
              </InputGroupAddon>
              <InputText
                v-model="searchQuery"
                placeholder="Filtrer canaux ou contacts..."
                class="border-0 bg-transparent w-full focus:ring-0"
                :class="theme === 'dark' ? 'text-gray-200 placeholder-gray-500' : 'text-slate-800'"
              />
            </InputGroup>
          </div>

          <div class="flex-1 overflow-y-auto custom-scrollbar">
            <div class="p-3">
              <!-- Canaux -->
              <div class="mb-6">
                <div class="flex items-center justify-between px-3 mb-2">
                  <h3 class="text-xs font-black uppercase tracking-widest" :class="theme === 'dark' ? 'text-gray-500' : 'text-slate-400'">Canaux</h3>
                  <Button
                    icon="pi pi-plus"
                    class="p-button-rounded p-button-text p-button-sm w-6 h-6 p-0"
                    :class="theme === 'dark' ? 'text-gray-400 hover:text-gray-200' : 'text-slate-400'"
                    @click="openCreateChannelDialog"
                  />
                </div>
                <ul class="space-y-0.5">
                  <li
                    v-for="channel in filteredChannels"
                    :key="channel.id"
                    @click="selectConversation(channel)"
                    :class="[
                      'flex items-center justify-between px-3 py-2 rounded-xl cursor-pointer transition-all duration-150',
                      activeConversation?.id === channel.id
                        ? theme === 'dark' ? 'bg-indigo-900/50 text-indigo-300 font-bold' : 'bg-indigo-50 text-indigo-700 font-bold'
                        : theme === 'dark' ? 'hover:bg-gray-700 text-gray-300' : 'hover:bg-slate-50 text-slate-700',
                    ]"
                  >
                    <div class="flex items-center gap-3 truncate">
                      <i :class="[channel.icon, 'text-sm', activeConversation?.id === channel.id ? 'text-indigo-400' : (theme === 'dark' ? 'text-gray-500' : 'text-slate-400')]"></i>
                      <span class="truncate text-sm">{{ channel.name }}</span>
                    </div>
                    <Badge v-if="channel.unread" :value="channel.unread" severity="danger" />
                  </li>
                </ul>
              </div>

              <!-- Messages directs -->
              <div>
                <div class="flex items-center justify-between px-3 mb-2">
                  <h3 class="text-xs font-black uppercase tracking-widest" :class="theme === 'dark' ? 'text-gray-500' : 'text-slate-400'">Messages Directs</h3>
                  <Button
                    icon="pi pi-plus"
                    class="p-button-rounded p-button-text p-button-sm w-6 h-6 p-0"
                    :class="theme === 'dark' ? 'text-gray-400 hover:text-gray-200' : 'text-slate-400'"
                    @click="openNewChatDialog"
                  />
                </div>
                <ul class="space-y-0.5">
                  <li
                    v-for="dm in filteredDMs"
                    :key="dm.id"
                    @click="selectConversation(dm)"
                    :class="[
                      'flex items-center justify-between px-3 py-2 rounded-xl cursor-pointer transition-all',
                      activeConversation?.id === dm.id ? (theme === 'dark' ? 'bg-indigo-900/50' : 'bg-indigo-50') : (theme === 'dark' ? 'hover:bg-gray-700' : 'hover:bg-slate-50'),
                    ]"
                  >
                    <div class="flex items-center gap-3 truncate w-full">
                      <div class="relative flex-shrink-0">
                        <Avatar :image="dm.user.avatar" shape="circle" class="w-8 h-8 shadow-sm border" :class="theme === 'dark' ? 'border-gray-700' : 'border-slate-100'" />
                        <span :class="['absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full border-2 border-white', getStatusColor(dm.user.status)]"></span>
                      </div>
                      <div class="flex flex-col truncate w-full pr-2">
                        <div class="flex justify-between items-center w-full">
                          <span class="truncate text-sm" :class="activeConversation?.id === dm.id ? 'font-bold text-indigo-500' : 'font-medium'">{{ dm.user.name }}</span>
                          <span class="text-[10px] flex-shrink-0" :class="theme === 'dark' ? 'text-gray-500' : 'text-slate-400'">{{ dm.time }}</span>
                        </div>
                        <span class="truncate text-xs" :class="dm.unread && activeConversation?.id !== dm.id ? 'font-bold' : 'text-gray-500'">{{ dm.last_message }}</span>
                      </div>
                    </div>
                    <div v-if="dm.unread && activeConversation?.id !== dm.id" class="w-2 h-2 bg-indigo-500 rounded-full flex-shrink-0"></div>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Profil utilisateur -->
          <div class="p-4 border-t" :class="theme === 'dark' ? 'border-gray-700 bg-gray-800/50' : 'border-slate-200 bg-slate-50'">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <Avatar :image="currentUser?.avatar" icon="pi pi-user" class="w-10 h-10 rounded-xl" :class="theme === 'dark' ? 'bg-gray-700 text-gray-300' : 'bg-indigo-100 text-indigo-600'" />
                <div class="flex flex-col">
                  <span class="text-sm font-bold">{{ currentUser?.name }}</span>
                  <span class="text-[10px] font-bold uppercase flex items-center gap-1 text-emerald-500">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 block"></span> En ligne
                  </span>
                </div>
              </div>
              <div class="flex gap-1">
                <Button icon="pi pi-sun" v-if="theme === 'dark'" class="p-button-rounded p-button-text p-button-secondary !w-8 !h-8" @click="toggleTheme" />
                <Button icon="pi pi-moon" v-else class="p-button-rounded p-button-text p-button-secondary !w-8 !h-8" @click="toggleTheme" />
                <Button icon="pi pi-cog" class="p-button-rounded p-button-text p-button-secondary !w-8 !h-8" />
              </div>
            </div>
          </div>
        </div>

        <!-- ZONE CENTRALE CHAT -->
        <div class="flex-1 flex flex-col relative" :class="theme === 'dark' ? 'bg-gray-900' : 'bg-white'">
          <!-- Loading skeleton -->
          <div v-if="isDataLoading" class="absolute inset-0 z-20 p-6 flex flex-col" :class="theme === 'dark' ? 'bg-gray-900' : 'bg-white'">
            <div class="flex items-center gap-4 mb-10 pb-6 border-b" :class="theme === 'dark' ? 'border-gray-800' : 'border-slate-200'">
              <Skeleton shape="circle" size="3rem" />
              <div class="space-y-2"><Skeleton width="150px" /><Skeleton width="100px" height="0.5rem" /></div>
            </div>
            <div class="flex-1 space-y-6">
              <div class="flex gap-4"><Skeleton shape="circle" size="2.5rem" /><Skeleton width="40%" height="4rem" borderRadius="1rem" /></div>
              <div class="flex gap-4 flex-row-reverse"><Skeleton shape="circle" size="2.5rem" /><Skeleton width="30%" height="3rem" borderRadius="1rem" /></div>
              <div class="flex gap-4"><Skeleton shape="circle" size="2.5rem" /><Skeleton width="60%" height="6rem" borderRadius="1rem" /></div>
            </div>
          </div>

          <template v-else-if="activeConversation">
            <!-- Header conversation -->
            <div class="px-6 py-4 border-b flex justify-between items-center shadow-sm z-10 flex-shrink-0" :class="theme === 'dark' ? 'bg-gray-800 border-gray-700' : 'bg-white border-slate-200'">
              <div class="flex items-center gap-4">
                <template v-if="activeConversation.type === 'dm'">
                  <div class="relative">
                    <Avatar :image="activeConversation.user.avatar" size="large" shape="circle" class="shadow-sm border" :class="theme === 'dark' ? 'border-gray-700' : 'border-slate-100'" />
                    <span :class="['absolute bottom-0 right-0 w-3.5 h-3.5 rounded-full border-2 border-white', getStatusColor(activeConversation.user.status)]"></span>
                  </div>
                  <div>
                    <h2 class="text-lg font-black">{{ activeConversation.user.name }}</h2>
                    <p class="text-xs font-medium" :class="theme === 'dark' ? 'text-gray-400' : 'text-slate-500'">{{ activeConversation.user.role }}</p>
                  </div>
                </template>
                <template v-else>
                  <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl shadow-inner border" :class="theme === 'dark' ? 'bg-gray-700 text-gray-300 border-gray-600' : 'bg-slate-100 text-slate-600 border-slate-200'">
                    <i :class="activeConversation.icon"></i>
                  </div>
                  <div>
                    <h2 class="text-lg font-black">{{ activeConversation.name }}</h2>
                    <p class="text-xs font-medium flex items-center gap-2" :class="theme === 'dark' ? 'text-gray-400' : 'text-slate-500'">
                      <span>{{ channelMembers.length }} Membres</span> • <span class="truncate max-w-xs">{{ activeConversation.description }}</span>
                    </p>
                  </div>
                </template>
              </div>
              <div class="flex items-center gap-2">
                <Button icon="pi pi-phone" class="p-button-rounded p-button-text p-button-secondary" />
                <Button icon="pi pi-video" class="p-button-rounded p-button-text p-button-secondary" />
                <div class="w-px h-6 bg-slate-200 mx-1"></div>
                <Button :icon="isRightSidebarOpen ? 'pi pi-angle-right' : 'pi pi-info-circle'" class="p-button-rounded p-button-text p-button-secondary" @click="toggleRightSidebar" />
              </div>
            </div>

            <!-- Messages scrollable -->
            <div ref="messagesContainer" class="flex-1 overflow-y-auto px-6 py-4 custom-scrollbar" :class="theme === 'dark' ? 'bg-gray-900' : 'bg-slate-50/50'">
              <div class="flex flex-col min-h-full justify-end">
                <!-- Welcome header -->
                <div class="text-center my-8">
                  <div v-if="activeConversation.type === 'dm'" class="flex flex-col items-center">
                    <Avatar :image="activeConversation.user.avatar" size="xlarge" shape="circle" class="w-24 h-24 shadow-lg mb-4 border-4 border-white" />
                    <h3 class="text-xl font-black">{{ activeConversation.user.name }}</h3>
                    <p class="text-sm mt-2" :class="theme === 'dark' ? 'text-gray-400' : 'text-slate-500'">Ceci est le début de votre historique de messages directs.</p>
                  </div>
                  <div v-else class="flex flex-col items-center">
                    <div class="w-24 h-24 rounded-[2rem] bg-indigo-100 text-indigo-500 flex items-center justify-center text-4xl shadow-lg mb-4 border-4 border-white">
                      <i :class="activeConversation.icon"></i>
                    </div>
                    <h3 class="text-xl font-black">Bienvenue dans #{{ activeConversation.name }}</h3>
                    <p class="text-sm mt-2 max-w-md" :class="theme === 'dark' ? 'text-gray-400' : 'text-slate-500'">{{ activeConversation.description }}</p>
                  </div>
                </div>

                <!-- Messages -->
                <div v-for="(msg, idx) in activeMessages" :key="msg.id" class="mb-4 group">
                  <div v-if="idx === 0 || shouldShowDateSeparator(msg, activeMessages[idx - 1])" class="flex items-center justify-center my-6">
                    <span class="text-xs font-bold px-4 py-1 rounded-full shadow-sm" :class="theme === 'dark' ? 'bg-gray-800 border-gray-700 text-gray-400' : 'bg-white border border-slate-200 text-slate-500'">
                      {{ formatDateDivider(msg.created_at) }}
                    </span>
                  </div>

                  <div :class="['flex gap-4 w-full', msg.user_id === currentUser.id ? 'flex-row-reverse' : 'flex-row']">
                    <Avatar v-if="msg.user_id !== currentUser.id" :image="msg.user?.avatar" shape="circle" class="w-10 h-10 shadow-sm flex-shrink-0 mt-auto" />

                    <div :class="['flex flex-col max-w-[70%]', msg.user_id === currentUser.id ? 'items-end' : 'items-start']">
                      <div v-if="msg.user_id !== currentUser.id && activeConversation.type !== 'dm'" class="flex items-baseline gap-2 mb-1 ml-1">
                        <span class="text-xs font-black" :class="theme === 'dark' ? 'text-gray-200' : 'text-slate-700'">{{ msg.user?.name }}</span>
                        <span class="text-[10px]" :class="theme === 'dark' ? 'text-gray-500' : 'text-slate-400'">{{ formatTime(msg.created_at) }}</span>
                      </div>

                      <div class="flex items-center gap-2 relative">
                        <div :class="['opacity-0 group-hover:opacity-100 transition-opacity bg-white border rounded-lg shadow-sm flex items-center absolute -top-4 z-10', msg.user_id === currentUser.id ? '-left-20' : '-right-20', theme === 'dark' ? 'bg-gray-700 border-gray-600' : 'border-slate-200']">
                          <Button icon="pi pi-reply" class="p-button-text p-button-sm !w-8 !h-8" :class="theme === 'dark' ? 'text-gray-300' : 'text-slate-500'" @click="replyToMessage(msg)" />
                          <Button icon="pi pi-ellipsis-v" class="p-button-text p-button-sm !w-8 !h-8" :class="theme === 'dark' ? 'text-gray-300' : 'text-slate-500'" @click="onMessageRightClick($event, msg)" />
                        </div>

                        <div
                          @contextmenu.prevent="onMessageRightClick($event, msg)"
                          :class="[
                            'px-5 py-3 shadow-sm relative text-sm leading-relaxed',
                            msg.user_id === currentUser.id
                              ? theme === 'dark' ? 'bg-indigo-700 text-white rounded-[1.5rem] rounded-br-sm' : 'bg-indigo-600 text-white rounded-[1.5rem] rounded-br-sm'
                              : theme === 'dark' ? 'bg-gray-800 border border-gray-700 text-gray-200 rounded-[1.5rem] rounded-bl-sm' : 'bg-white border border-slate-100 text-slate-800 rounded-[1.5rem] rounded-bl-sm',
                          ]"
                        >
                          <p v-if="msg.body" class="whitespace-pre-wrap break-words">{{ msg.body }}</p>

                          <!-- Pièces jointes -->
                          <div v-if="msg.attachments && msg.attachments.length" class="mt-3 flex flex-col gap-2">
                            <div v-for="att in msg.attachments" :key="att.id" :class="['flex items-center gap-3 p-3 rounded-xl border', msg.user_id === currentUser.id ? 'bg-indigo-500/50 border-indigo-400' : theme === 'dark' ? 'bg-gray-700 border-gray-600' : 'bg-slate-50 border-slate-200']">
                              <div :class="['w-10 h-10 rounded-lg flex items-center justify-center text-xl', getFileColorClass(att.mime_type)]">
                                <i :class="getFileTypeIcon(att.mime_type)"></i>
                              </div>
                              <div class="flex flex-col overflow-hidden flex-1">
                                <span class="font-bold text-sm truncate">{{ att.file_name }}</span>
                                <span class="text-[10px]" :class="msg.user_id === currentUser.id ? 'text-indigo-200' : theme === 'dark' ? 'text-gray-400' : 'text-slate-400'">{{ formatFileSize(att.size) }}</span>
                                <audio v-if="att.mime_type.startsWith('audio')" controls class="mt-2 w-full"><source :src="getAttachmentUrl(att)" /></audio>
                                <img v-if="att.mime_type.startsWith('image')" :src="getAttachmentUrl(att)" class="mt-2 rounded-lg max-h-40 object-cover border" />
                                <a v-if="att.mime_type === 'application/pdf'" :href="getAttachmentUrl(att)" target="_blank" class="mt-2 text-xs underline">Ouvrir le PDF</a>
                              </div>
                              <Button icon="pi pi-download" class="p-button-rounded p-button-text p-button-sm ml-auto" @click="downloadAttachment(att)" />
                            </div>
                          </div>

                          <!-- Réactions -->
                          <div v-if="msg.reactions && msg.reactions.length" class="flex flex-wrap gap-1 mt-2">
                            <div v-for="(reaction, rIdx) in groupedReactions(msg.reactions)" :key="rIdx" @click="toggleReaction(msg, reaction.emoji)" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs cursor-pointer hover:bg-white/20" :class="theme === 'dark' ? 'bg-gray-700 text-gray-300' : 'bg-slate-100 text-slate-700'">
                              <span>{{ reaction.emoji }}</span>
                              <span>{{ reaction.count }}</span>
                            </div>
                          </div>
                        </div>
                      </div>

                      <div :class="['flex items-center gap-1 mt-1 px-1', msg.user_id === currentUser.id ? 'justify-end' : 'justify-start']">
                        <span class="text-[10px]" :class="theme === 'dark' ? 'text-gray-500' : 'text-slate-400'">{{ formatTime(msg.created_at) }}</span>
                        <i v-if="msg.user_id === currentUser.id" :class="['pi text-[10px]', msg.is_read ? 'pi-check-circle text-blue-500' : 'pi-check text-slate-400']"></i>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Typing indicator -->
                <div v-if="isTyping" class="flex items-center gap-4 w-full mt-4 animate-fadein">
                  <Avatar :image="activeConversation.user?.avatar" shape="circle" class="w-8 h-8 shadow-sm" />
                  <div class="bg-white border border-slate-100 rounded-full px-4 py-2 flex items-center gap-1 shadow-sm w-16 h-8">
                    <span class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                    <span class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                    <span class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Zone de saisie -->
            <div class="p-4 border-t z-10 flex-shrink-0" :class="theme === 'dark' ? 'bg-gray-800 border-gray-700' : 'bg-white border-slate-200'">
              <div class="rounded-2xl flex flex-col focus-within:ring-2 focus-within:ring-indigo-500/30 transition-shadow" :class="theme === 'dark' ? 'bg-gray-700 border-gray-600' : 'bg-slate-50 border border-slate-200'">
                <Textarea
                  v-model="messageText"
                  autoResize
                  rows="1"
                  placeholder="Écrivez un message..."
                  class="w-full bg-transparent border-none shadow-none focus:ring-0 p-4 resize-none max-h-32 text-sm"
                  :class="theme === 'dark' ? 'text-gray-200 placeholder-gray-500' : 'text-slate-800'"
                  @keydown.enter.exact.prevent="sendMessage"
                />

                <div class="px-3 pb-3">
                  <div v-if="selectedFiles.length" class="mb-3 space-y-2">
                    <div v-for="(f, i) in selectedFiles" :key="i" class="flex items-center justify-between rounded-xl p-3" :class="theme === 'dark' ? 'bg-gray-800 border-gray-700' : 'bg-white border border-slate-200'">
                      <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center" :class="getFileColorClass(f.type)"><i :class="getFileTypeIcon(f.type)"></i></div>
                        <div class="min-w-0"><p class="text-sm font-bold truncate">{{ f.name }}</p><p class="text-[10px] text-slate-500">{{ f.size }}</p></div>
                      </div>
                      <Button icon="pi pi-times" class="p-button-text p-button-sm" @click="removeSelectedFile(i)" />
                    </div>
                  </div>
                </div>

                <div class="flex items-center justify-between px-4 py-3 rounded-b-2xl" :class="theme === 'dark' ? 'border-t border-gray-700 bg-gray-800/50' : 'border-t border-slate-200/80 bg-slate-50/50'">
                  <div class="flex items-center gap-1.5">
                    <Button icon="pi pi-plus" class="p-button-rounded p-button-text !w-9 !h-9 transition-colors" :class="theme === 'dark' ? 'text-gray-400 hover:bg-gray-700 hover:text-gray-200' : 'text-slate-500 hover:bg-slate-200 hover:text-slate-800'" @click="toggleRightSidebar" v-tooltip.top="'Plus d’options'" />
                    <div class="w-px h-5" :class="theme === 'dark' ? 'bg-gray-600' : 'bg-slate-300'"></div>
                    <Button icon="pi pi-paperclip" class="p-button-rounded p-button-text !w-9 !h-9 transition-colors" :class="theme === 'dark' ? 'text-gray-400 hover:bg-blue-900/50 hover:text-blue-400' : 'text-slate-500 hover:bg-blue-50 hover:text-blue-600'" @click="triggerFileSelect" v-tooltip.top="'Joindre un fichier'" />
                    <Button icon="pi pi-microphone" class="p-button-rounded p-button-text !w-9 !h-9 transition-colors" :class="theme === 'dark' ? 'text-gray-400 hover:bg-rose-900/50 hover:text-rose-400' : 'text-slate-500 hover:bg-rose-50 hover:text-rose-600'" @click="startRecording" v-tooltip.top="'Message vocal'" />
                  </div>
                  <div class="flex items-center gap-4">
                    <div class="hidden md:flex items-center gap-2 text-[11px] font-medium select-none cursor-default" :class="theme === 'dark' ? 'text-gray-500' : 'text-slate-400'">
                      <span>Envoyer avec</span>
                      <kbd class="px-2 py-0.5 rounded-md shadow-sm font-sans font-bold flex items-center gap-1" :class="theme === 'dark' ? 'bg-gray-700 border-gray-600 text-gray-300' : 'bg-white border border-slate-200 text-slate-600'">
                        <i class="pi pi-level-down text-[9px] rotate-90"></i> Entrée
                      </kbd>
                    </div>
                    <Button
                      icon="pi pi-send"
                      :disabled="!messageText.trim() && selectedFiles.length === 0"
                      class="border-none rounded-xl shadow-md shadow-indigo-500/30 !w-10 !h-10 p-0 flex items-center justify-center transition-all duration-200 transform active:scale-95"
                      :class="!messageText.trim() && selectedFiles.length === 0 ? 'bg-slate-200 text-slate-400 shadow-none cursor-not-allowed' : 'bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white'"
                      @click="sendMessage"
                      v-tooltip.top="'Envoyer le message'"
                    />
                  </div>
                </div>
              </div>
            </div>
          </template>

          <!-- Aucune conversation -->
          <div v-else class="flex-1 flex flex-col items-center justify-center p-6" :class="theme === 'dark' ? 'bg-gray-900' : 'bg-slate-50/50'">
            <div class="w-32 h-32 rounded-full flex items-center justify-center text-6xl mb-6 shadow-inner" :class="theme === 'dark' ? 'bg-gray-800 text-gray-600' : 'bg-indigo-50 text-indigo-300'">
              <i class="pi pi-comments"></i>
            </div>
            <h2 class="text-2xl font-black" :class="theme === 'dark' ? 'text-gray-200' : 'text-slate-800'">Vos Messages</h2>
            <p class="mt-2 max-w-md text-center" :class="theme === 'dark' ? 'text-gray-400' : 'text-slate-500'">Sélectionnez une conversation dans la barre latérale pour commencer à discuter.</p>
            <Button label="Nouvelle Conversation" icon="pi pi-pen-to-square" class="mt-8 bg-indigo-600 border-none rounded-xl font-bold shadow-lg" @click="openNewChatDialog" />
          </div>
        </div>

        <!-- SIDEBAR DROITE (gestion des membres enrichie) -->
        <div
          :class="[
            'w-80 flex flex-col flex-shrink-0 transition-all duration-300 overflow-hidden',
            isRightSidebarOpen && activeConversation ? 'translate-x-0 ml-0 border-l' : 'translate-x-full absolute right-0 opacity-0 w-0 border-none',
            theme === 'dark' ? 'bg-gray-800 border-gray-700' : 'bg-slate-50 border-l border-slate-200',
          ]"
        >
          <div class="h-full overflow-y-auto custom-scrollbar">
            <div v-if="activeConversation" class="p-6">
              <div class="flex flex-col items-center text-center mb-6">
                <template v-if="activeConversation.type === 'dm'">
                  <Avatar :image="activeConversation.user.avatar" size="xlarge" shape="circle" class="w-24 h-24 shadow-md mb-4 border-4 border-white" />
                  <h3 class="text-xl font-black" :class="theme === 'dark' ? 'text-gray-200' : 'text-slate-800'">{{ activeConversation.user.name }}</h3>
                  <p class="text-sm font-bold mt-1 text-indigo-600">{{ activeConversation.user.role }}</p>
                </template>
                <template v-else>
                  <div class="w-20 h-20 rounded-3xl flex items-center justify-center text-3xl shadow-md mb-4 border relative" :class="theme === 'dark' ? 'bg-gray-700 text-indigo-400 border-gray-600' : 'bg-white text-indigo-500 border-slate-100'">
                    <i :class="activeConversation.icon"></i>
                    <Button
                      v-if="isChannelAdmin"
                      icon="pi pi-cog"
                      class="p-button-rounded p-button-text !w-6 !h-6 absolute -bottom-2 -right-2 text-xs shadow-md"
                      :class="theme === 'dark' ? 'bg-gray-700 text-gray-300' : 'bg-white text-slate-600'"
                      @click="openManageMembers"
                    />
                  </div>
                  <h3 class="text-xl font-black">#{{ activeConversation.name }}</h3>
                  <p class="text-xs mt-2 leading-relaxed" :class="theme === 'dark' ? 'text-gray-400' : 'text-slate-500'">{{ activeConversation.description }}</p>
                  <Button
                    v-if="activeConversation.type === 'channel'"
                    label="Gérer les membres"
                    icon="pi pi-users"
                    class="mt-3 !text-xs !py-1 !px-2 rounded-full"
                    :class="theme === 'dark' ? 'bg-gray-700 hover:bg-gray-600 text-gray-200' : 'bg-white border border-slate-200 hover:bg-slate-50 text-slate-700'"
                    @click="openManageMembers"
                  />
                </template>
              </div>

              <Accordion :activeIndex="[0, 1]" :multiple="true" class="custom-accordion">
                <AccordionTab header="À propos">
                  <div class="space-y-3">
                    <div class="flex flex-col">
                      <span class="text-[10px] uppercase font-bold" :class="theme === 'dark' ? 'text-gray-500' : 'text-slate-400'">Création</span>
                      <span class="text-sm font-medium">{{ formatDate(activeConversation.created_at) }}</span>
                    </div>
                    <div class="flex flex-col">
                      <span class="text-[10px] uppercase font-bold" :class="theme === 'dark' ? 'text-gray-500' : 'text-slate-400'">Créé par</span>
                      <span class="text-sm font-medium">{{ activeConversation.creator?.name || 'Admin' }}</span>
                    </div>
                  </div>
                </AccordionTab>

                <!-- ONGLET MEMBRES (détaillé) -->
                <AccordionTab header="Membres">
                  <div class="space-y-2">
                    <div
                      v-for="member in channelMembers"
                      :key="member.id"
                      class="flex items-center justify-between p-2 rounded-xl group"
                      :class="theme === 'dark' ? 'hover:bg-gray-700' : 'hover:bg-slate-100'"
                    >
                      <div class="flex items-center gap-3">
                        <Avatar :image="member.avatar" shape="circle" class="w-8 h-8" />
                        <div class="flex flex-col">
                          <span class="text-sm font-medium">{{ member.name }}</span>
                          <span class="text-[10px]" :class="theme === 'dark' ? 'text-gray-400' : 'text-slate-500'">
                            {{ member.role === 'admin' ? 'Administrateur' : (member.role === 'moderator' ? 'Modérateur' : 'Membre') }}
                          </span>
                        </div>
                      </div>
                      <div v-if="isChannelAdmin && member.id !== currentUser.id" class="relative">
                        <Button
                          icon="pi pi-ellipsis-v"
                          class="p-button-rounded p-button-text !w-7 !h-7 opacity-0 group-hover:opacity-100 transition-opacity"
                          :class="theme === 'dark' ? 'text-gray-400 hover:text-gray-200' : 'text-slate-500 hover:text-slate-800'"
                          @click="toggleMemberMenu($event, member)"
                        />
                      </div>
                    </div>
                    <Button
                      v-if="isChannelAdmin"
                      label="Ajouter des membres"
                      icon="pi pi-plus"
                      class="w-full mt-2 !text-xs rounded-lg"
                      :class="theme === 'dark' ? 'bg-indigo-600 hover:bg-indigo-500' : 'bg-indigo-600 hover:bg-indigo-500 text-white'"
                      @click="openAddMemberDialog"
                    />
                  </div>
                </AccordionTab>

                <AccordionTab header="Fichiers partagés">
                  <div class="space-y-2">
                    <div v-for="(file, idx) in sharedFiles" :key="idx" class="flex items-center gap-3 p-2 rounded-xl shadow-sm" :class="theme === 'dark' ? 'bg-gray-700 border-gray-600' : 'bg-white border border-slate-100'">
                      <div class="w-10 h-10 rounded-lg flex items-center justify-center" :class="getFileColorClass(file.mime_type)"><i :class="getFileTypeIcon(file.mime_type)"></i></div>
                      <div class="flex flex-col overflow-hidden flex-1">
                        <span class="text-xs font-bold truncate">{{ file.file_name }}</span>
                        <span class="text-[10px]" :class="theme === 'dark' ? 'text-gray-400' : 'text-slate-400'">{{ formatFileSize(file.size) }}</span>
                      </div>
                      <Button icon="pi pi-download" class="p-button-rounded p-button-text p-button-sm" @click="downloadAttachment(file)" />
                    </div>
                  </div>
                </AccordionTab>
              </Accordion>
            </div>
          </div>
        </div>
      </div>

      <!-- Menus contextuels -->
      <ContextMenu ref="messageContextMenu" :model="contextMenuItems" />
      <Menu ref="memberMenu" :model="memberContextMenuItems" popup />

      <!-- Dialogue ajout membres -->
      <Dialog v-model:visible="addMemberDialogVisible" header="Ajouter des membres" :style="{ width: '450px' }" class="custom-dialog" :modal="true">
        <div class="flex flex-col gap-4">
          <MultiSelect
            v-model="selectedNewMembers"
            :options="availableUsers"
            optionLabel="name"
            placeholder="Rechercher des utilisateurs..."
            class="w-full rounded-xl"
            display="chip"
            filter
          >
            <template #option="slotProps">
              <div class="flex items-center gap-2"><Avatar :image="slotProps.option.avatar" shape="circle" class="w-6 h-6" /><span class="text-sm font-bold">{{ slotProps.option.name }}</span></div>
            </template>
          </MultiSelect>
        </div>
        <template #footer>
          <Button label="Annuler" class="p-button-text" @click="addMemberDialogVisible = false" />
          <Button label="Ajouter" icon="pi pi-check" class="bg-indigo-600 border-none" @click="addMembersToChannel" />
        </template>
      </Dialog>

      <!-- Dialogue création canal (amélioré) -->
      <Dialog v-model:visible="newChannelDialog" modal :style="{ width: '500px' }" class="custom-dialog" :closable="false">
        <template #header><div class="flex items-center justify-between w-full"><h2 class="font-black text-xl flex items-center gap-2"><i class="pi pi-hashtag text-indigo-500"></i> Créer un Canal</h2><Button icon="pi pi-times" class="p-button-rounded p-button-text p-button-secondary" @click="closeChannelDialog" /></div></template>
        <div class="space-y-5 pt-4">
          <div class="flex flex-col gap-2"><label class="text-sm font-bold">Nom du canal</label><InputGroup class="rounded-xl overflow-hidden border" :class="theme === 'dark' ? 'border-gray-700' : 'border-slate-200'"><InputGroupAddon class="bg-slate-50 border-0 font-bold text-slate-400">#</InputGroupAddon><InputText v-model="newChannelForm.name" placeholder="ex: projet-alpha" class="border-0 w-full" /></InputGroup></div>
          <div class="flex flex-col gap-2"><label class="text-sm font-bold">Description</label><Textarea v-model="newChannelForm.description" rows="2" class="w-full rounded-xl" placeholder="Sujet de discussion du canal..." /></div>
          <div class="p-4 rounded-xl border flex items-center justify-between" :class="theme === 'dark' ? 'bg-gray-800 border-gray-700' : 'bg-slate-50 border-slate-200'"><p class="font-bold text-sm">Canal Privé</p><ToggleButton v-model="newChannelForm.is_private" onLabel="Oui" offLabel="Non" /></div>
        </div>
        <template #footer><div class="flex justify-end gap-3 mt-4 pt-4 border-t" :class="theme === 'dark' ? 'border-gray-700' : 'border-slate-100'"><Button label="Annuler" class="p-button-text font-bold" @click="closeChannelDialog" /><Button label="Créer le canal" icon="pi pi-check" class="bg-indigo-600 border-none font-bold rounded-xl" @click="createNewChannel" /></div></template>
      </Dialog>

      <!-- Dialogue nouvelle conversation (DM) amélioré -->
      <Dialog v-model:visible="newChatDialog" modal :style="{ width: '600px' }" class="custom-dialog" :closable="false">
        <template #header><div class="flex items-center justify-between w-full"><h2 class="font-black text-xl" :class="theme === 'dark' ? 'text-gray-200' : 'text-slate-800'">Nouveau Message</h2><Button icon="pi pi-times" class="p-button-rounded p-button-text p-button-secondary" @click="closeNewChatDialog" /></div></template>
        <div class="space-y-6 pt-4">
          <div class="flex flex-col gap-2"><label class="text-sm font-bold">Destinataires</label><MultiSelect v-model="newChatForm.to" :options="usersList" optionLabel="name" placeholder="Rechercher des collaborateurs..." class="w-full rounded-xl" display="chip" filter><template #option="slotProps"><div class="flex items-center gap-2"><Avatar :image="slotProps.option.avatar" shape="circle" class="w-6 h-6" /><span class="text-sm font-bold">{{ slotProps.option.name }}</span></div></template></MultiSelect></div>
          <div class="flex flex-col gap-2"><label class="text-sm font-bold">Message Optionnel</label><Editor v-model="newChatForm.message" editorStyle="height: 150px" /></div>
        </div>
        <template #footer><div class="flex justify-end gap-3 mt-4 pt-4 border-t" :class="theme === 'dark' ? 'border-gray-700' : 'border-slate-100'"><Button label="Annuler" class="p-button-text font-bold" @click="closeNewChatDialog" /><Button label="Démarrer la discussion" icon="pi pi-send" class="bg-indigo-600 border-none font-bold rounded-xl" @click="createNewChat" /></div></template>
      </Dialog>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import AppLayout from '@/sakai/layout/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import axios from 'axios'
import Menu from 'primevue/menu'

// PrimeVue components (tous)
import Avatar from 'primevue/avatar'
import Badge from 'primevue/badge'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Dialog from 'primevue/dialog'
import ContextMenu from 'primevue/contextmenu'
import MultiSelect from 'primevue/multiselect'
import Editor from 'primevue/editor'
import Accordion from 'primevue/accordion'
import AccordionTab from 'primevue/accordiontab'
import Skeleton from 'primevue/skeleton'
import InputGroup from 'primevue/inputgroup'
import InputGroupAddon from 'primevue/inputgroupaddon'
import ToggleButton from 'primevue/togglebutton'

// ----------------------------------------------------------------------
// Configuration Axios (authentification)
// ----------------------------------------------------------------------
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
const api = axios.create({
  baseURL: '/api/chat',
  withCredentials: true,
  headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken || '' }
})
api.interceptors.response.use(r => r, error => {
  if (error.response?.status === 401) {
    toast.add({ severity: 'error', summary: 'Session expirée', detail: 'Veuillez rafraîchir la page', life: 5000 })
  }
  return Promise.reject(error)
})

// ----------------------------------------------------------------------
// Initialisation des toasts et confirm
// ----------------------------------------------------------------------
const toast = useToast()
const confirm = useConfirm()

const props = defineProps({
  currentUser: { type: Object, required: true },
  users: { type: Array, default: () => [] },
})

// ----------------------------------------------------------------------
// États réactifs généraux
// ----------------------------------------------------------------------
const theme = ref(localStorage.getItem('chat_theme') || 'light')
const isDataLoading = ref(true)
const isRightSidebarOpen = ref(true)
const isMobileNavOpen = ref(false)
const searchQuery = ref('')
const activeConversation = ref(null)
const conversations = ref([])
const messagesDB = ref({})
const messageText = ref('')
const isTyping = ref(false)
const messagesContainer = ref(null)
let resizeObserver = null
let typingTimeout = null
let pollingInterval = null

// Dialogues
const newChatDialog = ref(false)
const newChannelDialog = ref(false)
const messageContextMenu = ref(null)
const selectedMessageContext = ref(null)

// Formulaires
const newChatForm = ref({ to: [], message: '' })
const newChannelForm = ref({ name: '', description: '', is_private: false })

// Fichiers
const fileInput = ref(null)
const selectedFiles = ref([])

// Gestion membres
const channelMembers = ref([])
const addMemberDialogVisible = ref(false)
const selectedNewMembers = ref([])
const availableUsers = ref([])
const memberMenu = ref(null)
const selectedMember = ref(null)

// ----------------------------------------------------------------------
// Computed
// ----------------------------------------------------------------------
const usersList = computed(() => props.users)
const filteredChannels = computed(() => conversations.value.filter(c => c.type === 'channel' && c.name?.toLowerCase().includes(searchQuery.value.toLowerCase())))
const filteredDMs = computed(() => conversations.value.filter(c => c.type === 'dm' && c.user?.name?.toLowerCase().includes(searchQuery.value.toLowerCase())))
const activeMessages = computed(() => messagesDB.value[activeConversation.value?.id] || [])
const sharedFiles = computed(() => { const files = []; for (const msg of activeMessages.value) if (msg.attachments?.length) files.push(...msg.attachments); return files })
const isChannelAdmin = computed(() => {
  if (!activeConversation.value || activeConversation.value.type !== 'channel') return false
  const current = channelMembers.value.find(m => m.id === props.currentUser.id)
  return current?.role === 'admin'
})

// ----------------------------------------------------------------------
// Menu contextuel des membres
// ----------------------------------------------------------------------
const memberContextMenuItems = ref([
  { label: 'Définir comme modérateur', icon: 'pi pi-star', command: () => updateMemberRole(selectedMember.value, 'moderator') },
  { label: 'Définir comme administrateur', icon: 'pi pi-crown', command: () => updateMemberRole(selectedMember.value, 'admin') },
  { label: 'Rétrograder en membre', icon: 'pi pi-user', command: () => updateMemberRole(selectedMember.value, 'member') },
  { label: 'Retirer du canal', icon: 'pi pi-trash', class: 'text-red-500', command: () => removeMemberFromChannel(selectedMember.value) },
])

// ----------------------------------------------------------------------
// Menu contextuel des messages
// ----------------------------------------------------------------------
const contextMenuItems = ref([
  { label: 'Répondre', icon: 'pi pi-reply', command: () => replyToMessage(selectedMessageContext.value) },
  { label: 'Copier le texte', icon: 'pi pi-copy', command: copyMessage },
  { label: 'Modifier', icon: 'pi pi-pencil', command: () => editMessage(selectedMessageContext.value) },
  { label: 'Supprimer', icon: 'pi pi-trash', class: 'text-red-500', command: deleteMessage },
])

// ----------------------------------------------------------------------
// Appels API (conversations, messages, membres)
// ----------------------------------------------------------------------
async function fetchConversations() {
  try {
    const res = await api.get('/conversations')
    conversations.value = res.data
    conversations.value.sort((a, b) => new Date(b.updated_at || b.time) - new Date(a.updated_at || a.time))
  } catch (error) { toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de charger les conversations', life: 3000 }) }
}

async function fetchMessages(conversationId, page = 1) {
  try {
    const res = await api.get(`/conversations/${conversationId}/messages`, { params: { page, limit: 50 } })
    messagesDB.value[conversationId] = res.data.data.reverse()
    return res.data.data
  } catch (error) { toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de charger les messages', life: 3000 }); return [] }
}

async function sendMessageApi(conversationId, body, attachments = []) {
  const formData = new FormData()
  if (body) formData.append('body', body)
  attachments.forEach(file => formData.append('attachments[]', file))
  const res = await api.post(`/conversations/${conversationId}/messages`, formData, { headers: { 'Content-Type': 'multipart/form-data' } })
  return res.data.data
}

async function updateMessageApi(messageId, newBody) { const res = await api.put(`/messages/${messageId}`, { body: newBody }); return res.data.data }
async function deleteMessageApi(messageId) { await api.delete(`/messages/${messageId}`) }
async function toggleReactionApi(messageId, reaction) { await api.post(`/messages/${messageId}/reactions`, { reaction }) }
async function createConversationApi(data) { const res = await api.post('/conversations', data); return res.data.conversation }
async function markAsReadApi(conversationId) { try { await api.post(`/conversations/${conversationId}/read`) } catch(e) {} }

// Gestion membres
async function fetchChannelMembers(channelId) {
  try {
    const res = await api.get(`/conversations/${channelId}/members`)
    channelMembers.value = res.data
    const memberIds = channelMembers.value.map(m => m.id)
    availableUsers.value = props.users.filter(u => !memberIds.includes(u.id))
  } catch (error) { toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de charger les membres', life: 3000 }) }
}

async function addMembersToChannel() {
  if (!selectedNewMembers.value.length) return
  const userIds = selectedNewMembers.value.map(u => u.id)
  try {
    await api.post(`/conversations/${activeConversation.value.id}/members`, { users: userIds })
    toast.add({ severity: 'success', summary: 'Ajouté', detail: 'Membres ajoutés au canal', life: 2000 })
    await fetchChannelMembers(activeConversation.value.id)
    selectedNewMembers.value = []
    addMemberDialogVisible.value = false
  } catch (error) { toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible d’ajouter les membres', life: 3000 }) }
}

async function removeMemberFromChannel(member) {
  confirm.require({
    message: `Retirer ${member.name} du canal ?`,
    header: 'Confirmation',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await api.delete(`/conversations/${activeConversation.value.id}/members/${member.id}`)
        toast.add({ severity: 'success', summary: 'Retiré', detail: `${member.name} a été retiré du canal.`, life: 2000 })
        await fetchChannelMembers(activeConversation.value.id)
      } catch (error) { toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de retirer le membre', life: 3000 }) }
    }
  })
}

async function updateMemberRole(member, newRole) {
  try {
    await api.put(`/conversations/${activeConversation.value.id}/members/${member.id}`, { role: newRole })
    toast.add({ severity: 'success', summary: 'Rôle mis à jour', detail: `${member.name} est maintenant ${newRole === 'admin' ? 'administrateur' : newRole === 'moderator' ? 'modérateur' : 'membre'}.`, life: 2000 })
    await fetchChannelMembers(activeConversation.value.id)
  } catch (error) { toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de modifier le rôle', life: 3000 }) }
}

// ----------------------------------------------------------------------
// Gestion des conversations
// ----------------------------------------------------------------------
async function selectConversation(conv) {
  if (activeConversation.value?.id === conv.id) return
  activeConversation.value = conv
  if (!messagesDB.value[conv.id]) {
    isDataLoading.value = true
    await fetchMessages(conv.id)
    isDataLoading.value = false
  } else nextTick(scrollToBottom)
  await markAsReadApi(conv.id)
  conv.unread = 0
  if (conv.type === 'channel') await fetchChannelMembers(conv.id)
  if (window.innerWidth < 1024) isMobileNavOpen.value = false
}

async function createNewChat() {
  if (!newChatForm.value.to.length) { toast.add({ severity: 'warn', summary: 'Aucun destinataire', detail: 'Veuillez sélectionner au moins une personne.', life: 3000 }); return }
  const userIds = newChatForm.value.to.map(u => u.id)
  try {
    const newConv = await createConversationApi({ type: 'dm', users: userIds })
    await fetchConversations()
    const added = conversations.value.find(c => c.id === newConv.id)
    if (added) {
      await selectConversation(added)
      if (newChatForm.value.message) { messageText.value = newChatForm.value.message; await sendMessage(); messageText.value = '' }
    }
    newChatDialog.value = false
    newChatForm.value = { to: [], message: '' }
  } catch (error) { toast.add({ severity: 'error', summary: 'Erreur', detail: error.response?.data?.error || 'Impossible de créer la discussion', life: 3000 }) }
}

async function createNewChannel() {
  if (!newChannelForm.value.name.trim()) { toast.add({ severity: 'warn', summary: 'Nom requis', detail: 'Veuillez donner un nom au canal.', life: 3000 }); return }
  try {
    const newConv = await createConversationApi({
      type: 'channel',
      name: newChannelForm.value.name.trim(),
      description: newChannelForm.value.description || '',
      is_private: newChannelForm.value.is_private,
      users: [props.currentUser.id],
    })
    await fetchConversations()
    const added = conversations.value.find(c => c.id === newConv.id)
    if (added) await selectConversation(added)
    newChannelDialog.value = false
    newChannelForm.value = { name: '', description: '', is_private: false }
  } catch (error) { toast.add({ severity: 'error', summary: 'Erreur', detail: error.response?.data?.error || 'Impossible de créer le canal', life: 3000 }) }
}

// ----------------------------------------------------------------------
// Envoi de message avec optimistic update
// ----------------------------------------------------------------------
async function sendMessage() {
  if (!messageText.value.trim() && !selectedFiles.value.length) return
  const convId = activeConversation.value.id
  const filesToSend = selectedFiles.value.map(f => f.file)
  const originalText = messageText.value
  const tempId = `temp_${Date.now()}`
  const newMsg = {
    id: tempId,
    user_id: props.currentUser.id,
    user: props.currentUser,
    body: originalText,
    created_at: new Date().toISOString(),
    attachments: selectedFiles.value.map(f => ({ file_name: f.name, size: f.size, mime_type: f.type })),
    reactions: [],
    is_sending: true,
  }
  if (!messagesDB.value[convId]) messagesDB.value[convId] = []
  messagesDB.value[convId].push(newMsg)
  scrollToBottom()
  messageText.value = ''
  const filesToClear = [...selectedFiles.value]
  selectedFiles.value = []
  if (fileInput.value) fileInput.value.value = ''
  try {
    const sent = await sendMessageApi(convId, originalText, filesToSend)
    const index = messagesDB.value[convId].findIndex(m => m.id === tempId)
    if (index !== -1) messagesDB.value[convId][index] = sent
    filesToClear.forEach(f => { if (f.url) URL.revokeObjectURL(f.url) })
    const conv = conversations.value.find(c => c.id === convId)
    if (conv) conv.last_message = originalText.substring(0, 50)
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Message non envoyé', life: 3000 })
    messagesDB.value[convId] = messagesDB.value[convId].filter(m => m.id !== tempId)
  }
}

// ----------------------------------------------------------------------
// Gestion fichiers
// ----------------------------------------------------------------------
function openFilePicker() { fileInput.value?.click() }
function handleFileChange(event) {
  const files = Array.from(event.target.files || [])
  selectedFiles.value = files.map(file => ({ file, name: file.name, size: (file.size / 1024 / 1024).toFixed(1) + ' MB', type: file.type.split('/')[0], url: URL.createObjectURL(file) }))
}
function removeSelectedFile(index) { const removed = selectedFiles.value.splice(index, 1)[0]; if (removed?.url) URL.revokeObjectURL(removed.url) }
function getAttachmentUrl(att) { return `/api/chat/attachments/${att.id}/download` }
async function downloadAttachment(att) { window.open(getAttachmentUrl(att), '_blank') }

// ----------------------------------------------------------------------
// Actions sur messages
// ----------------------------------------------------------------------
function onMessageRightClick(event, msg) { selectedMessageContext.value = msg; messageContextMenu.value.show(event) }
function replyToMessage(msg) { messageText.value = `@${msg.user?.name || 'User'} ` + messageText.value; toast.add({ severity: 'info', summary: 'Réponse', detail: 'Citation ajoutée', life: 2000 }) }
function copyMessage() { if (selectedMessageContext.value) { navigator.clipboard.writeText(selectedMessageContext.value.body || ''); toast.add({ severity: 'secondary', summary: 'Copié', detail: 'Message copié.', life: 2000 }) } }
function editMessage(msg) { const newBody = prompt('Modifier le message', msg.body); if (newBody && newBody !== msg.body) { updateMessageApi(msg.id, newBody).then(updated => { const idx = messagesDB.value[activeConversation.value.id].findIndex(m => m.id === msg.id); if (idx !== -1) messagesDB.value[activeConversation.value.id][idx] = updated; toast.add({ severity: 'success', summary: 'Modifié', detail: 'Message mis à jour', life: 2000 }) }).catch(() => toast.add({ severity: 'error', summary: 'Erreur', detail: 'Modification impossible', life: 3000 })) } }
function deleteMessage() { if (!selectedMessageContext.value) return; confirm.require({ message: 'Êtes-vous sûr de vouloir supprimer ce message ?', header: 'Confirmation', icon: 'pi pi-exclamation-triangle', acceptClass: 'p-button-danger', accept: async () => { try { await deleteMessageApi(selectedMessageContext.value.id); const convId = activeConversation.value.id; messagesDB.value[convId] = messagesDB.value[convId].filter(m => m.id !== selectedMessageContext.value.id); toast.add({ severity: 'success', summary: 'Supprimé', detail: 'Message retiré.', life: 3000 }) } catch { toast.add({ severity: 'error', summary: 'Erreur', detail: 'Suppression impossible', life: 3000 }) } } }) }
async function toggleReaction(msg, emoji) { try { await toggleReactionApi(msg.id, emoji); await fetchMessages(activeConversation.value.id) } catch { toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible d’ajouter la réaction', life: 2000 }) } }
function groupedReactions(reactions) { const groups = {}; for (const r of reactions) { groups[r.reaction] = groups[r.reaction] || { emoji: r.reaction, count: 0 }; groups[r.reaction].count++ } return Object.values(groups) }

// ----------------------------------------------------------------------
// Scroll automatique
// ----------------------------------------------------------------------
function scrollToBottom() { nextTick(() => { if (messagesContainer.value) messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight }) }
function setupScrollObserver() { if (resizeObserver) resizeObserver.disconnect(); if (messagesContainer.value) { resizeObserver = new ResizeObserver(() => scrollToBottom()); resizeObserver.observe(messagesContainer.value) } }
watch(activeMessages, () => scrollToBottom(), { deep: true })

// ----------------------------------------------------------------------
// Formattage (date, taille, statuts)
// ----------------------------------------------------------------------
function formatTime(date) { if (!date) return ''; return new Intl.DateTimeFormat('fr-FR', { hour: '2-digit', minute: '2-digit' }).format(new Date(date)) }
function formatDate(date) { if (!date) return ''; return new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(date)) }
function formatDateDivider(date) { if (!date) return ''; const d = new Date(date); const today = new Date(); const yesterday = new Date(today); yesterday.setDate(yesterday.getDate() - 1); if (d.toDateString() === today.toDateString()) return "Aujourd'hui"; if (d.toDateString() === yesterday.toDateString()) return 'Hier'; return new Intl.DateTimeFormat('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' }).format(d) }
function shouldShowDateSeparator(current, previous) { if (!previous) return true; return new Date(current.created_at).toDateString() !== new Date(previous.created_at).toDateString() }
function formatFileSize(bytes) { if (!bytes) return '0 B'; const k = 1024, sizes = ['B', 'KB', 'MB', 'GB']; const i = Math.floor(Math.log(bytes) / Math.log(k)); return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i] }
function getStatusColor(status) { const map = { online: 'bg-emerald-500', offline: 'bg-slate-400', busy: 'bg-rose-500', away: 'bg-amber-500' }; return map[status] || 'bg-slate-400' }
function getFileTypeIcon(mime) { if (mime.includes('pdf')) return 'pi pi-file-pdf'; if (mime.includes('image')) return 'pi pi-image'; if (mime.includes('audio')) return 'pi pi-volume-up'; if (mime.includes('word')) return 'pi pi-file-word'; if (mime.includes('excel')) return 'pi pi-file-excel'; return 'pi pi-file' }
function getFileColorClass(mime) { if (mime.includes('pdf')) return 'bg-rose-100 text-rose-500'; if (mime.includes('image')) return 'bg-emerald-100 text-emerald-500'; if (mime.includes('audio')) return 'bg-indigo-100 text-indigo-500'; if (mime.includes('word')) return 'bg-blue-100 text-blue-500'; if (mime.includes('excel')) return 'bg-green-100 text-green-600'; return 'bg-slate-100 text-slate-600' }

// ----------------------------------------------------------------------
// Thème et utilitaires UI
// ----------------------------------------------------------------------
function toggleTheme() { theme.value = theme.value === 'dark' ? 'light' : 'dark'; localStorage.setItem('chat_theme', theme.value); document.documentElement.classList.toggle('dark', theme.value === 'dark') }
function toggleRightSidebar() { isRightSidebarOpen.value = !isRightSidebarOpen.value }
function triggerFileSelect() { openFilePicker() }
function startRecording() { toast.add({ severity: 'info', summary: 'Fonctionnalité à venir', detail: 'Enregistrement vocal bientôt disponible', life: 2000 }) }
function toggleMemberMenu(event, member) { selectedMember.value = member; memberMenu.value.toggle(event) }
function openManageMembers() { if (activeConversation.value.type === 'channel') fetchChannelMembers(activeConversation.value.id) }
function openAddMemberDialog() { selectedNewMembers.value = []; addMemberDialogVisible.value = true }
function openCreateChannelDialog() { newChannelDialog.value = true }
function closeChannelDialog() { newChannelDialog.value = false; newChannelForm.value = { name: '', description: '', is_private: false } }
function openNewChatDialog() { newChatDialog.value = true }
function closeNewChatDialog() { newChatDialog.value = false; newChatForm.value = { to: [], message: '' } }

// ----------------------------------------------------------------------
// Polling pour nouveaux messages
// ----------------------------------------------------------------------
function startPolling() { if (pollingInterval) clearInterval(pollingInterval); pollingInterval = setInterval(async () => { if (activeConversation.value) { try { const res = await api.get(`/conversations/${activeConversation.value.id}/messages`, { params: { page: 1, limit: 1 } }); const latestRemote = res.data.data[0]; const localLatest = messagesDB.value[activeConversation.value.id]?.slice(-1)[0]; if (latestRemote && (!localLatest || latestRemote.id !== localLatest.id)) { await fetchMessages(activeConversation.value.id); scrollToBottom() } } catch (e) {} } }, 5000) }

// ----------------------------------------------------------------------
// Cycle de vie
// ----------------------------------------------------------------------
onMounted(async () => {
  document.documentElement.classList.toggle('dark', theme.value === 'dark')
  await fetchConversations()
  isDataLoading.value = false
  if (conversations.value.length) await selectConversation(conversations.value[0])
  nextTick(setupScrollObserver)
  startPolling()
})
onUnmounted(() => { if (resizeObserver) resizeObserver.disconnect(); if (pollingInterval) clearInterval(pollingInterval); if (typingTimeout) clearTimeout(typingTimeout) })
</script>

<style scoped>
.animate-fadein { animation: fadeIn 0.3s ease-in-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
.animate-bounce { animation: bounce 1s infinite; }
@keyframes bounce { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-4px); } }
.custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
:deep(.custom-dialog) { border-radius: 1.5rem; border: 1px solid #e2e8f0; box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25); }
:deep(.custom-dialog .p-dialog-header) { border-bottom: 1px solid #f1f5f9; padding: 1.5rem; }
:deep(.custom-dialog .p-dialog-content) { padding: 0 1.5rem 1.5rem 1.5rem; }
:deep(.custom-accordion .p-accordion-header-link) { background: transparent; border: none; border-top: 1px solid #e2e8f0; padding: 1.25rem 0; font-weight: 800; color: #1e293b; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; }
:deep(.custom-accordion .p-accordion-content) { border: none; background: transparent; padding: 0 0 1.5rem 0; }
.dark :deep(.custom-accordion .p-accordion-header-link) { color: #e2e8f0; border-top-color: #334155; }
.dark :deep(.custom-dialog) { background-color: #1f2937; border-color: #374151; }
.dark :deep(.custom-dialog .p-dialog-header) { border-bottom-color: #374151; }
.dark :deep(.custom-dialog .p-dialog-content) { color: #e5e7eb; }
</style>
